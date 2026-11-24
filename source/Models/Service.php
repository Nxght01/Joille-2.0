<?php

namespace Source\Models;

use PDO;
use PDOException;
use Source\Core\Model;
use Source\Core\Connect;

class Service extends Model
{
    private ?int $id;
    private ?int $categoriesId;
    private ?string $name;
    private ?string $description;
    private ?string $value;
    private ?int $active;

    public function __construct(
        ?int $id = null,
        ?int $categoriesId = null,
        ?string $name = null,
        ?string $description = null,
        ?string $value = null,
        ?int $active = 1
    ) {
        $this->id = $id;
        $this->categoriesId = $categoriesId;
        $this->name = $name;
        $this->description = $description;
        $this->value = $value;
        $this->active = $active;

        $this->table = 'services'; // nome da tabela do banco
        $this->primaryKey = 'id'; // nome da chave primária da tabela
        $this->fillable = ['categoriesId', 'name', 'description', 'value', 'active']; // camelCase
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCategoriesId(): ?int
    {
        return $this->categoriesId;
    }

    public function setCategoriesId(int $categoriesId): void
    {
        $this->categoriesId = $categoriesId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getActive(): ?int
    {
        return $this->active;
    }

    public function setActive(int $active): void
    {
        $this->active = $active;
    }

    // Listagem com o nome da categoria, usada pelo painel administrativo e pela área pública
    public function selectAllWithCategory(array $filters = [], ?string $orderBy = 'services.id', string $direction = 'ASC'): array
    {
        try {
            $query = "SELECT
                        services.id,
                        services.categories_id,
                        services.name,
                        services.description,
                        services.value,
                        services.active,
                        categories.name AS category_name,
                        -- capa do serviço: a imagem marcada como cover ou, na falta
                        -- dela, a mais antiga; e o total de imagens cadastradas
                        (SELECT path FROM service_images
                          WHERE service_images.services_id = services.id
                          ORDER BY service_images.cover DESC, service_images.id ASC
                          LIMIT 1) AS cover_image,
                        (SELECT COUNT(*) FROM service_images
                          WHERE service_images.services_id = services.id) AS images_count
                      FROM services
                      INNER JOIN categories ON services.categories_id = categories.id";

            if (!empty($filters)) {
                $query .= " WHERE ";
                foreach ($filters as $index => $filter) {
                    $query .= $filter;
                    if ($index < count($filters) - 1) {
                        $query .= " AND ";
                    }
                }
            }

            $query .= " ORDER BY {$orderBy} {$direction}";

            $stmt = Connect::getInstance()->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw $e;
            return [];
        }
    }

    public function selectByIdWithCategory(int $id): array | false
    {
        try {
            $query = "SELECT
                        services.id,
                        services.categories_id,
                        services.name,
                        services.description,
                        services.value,
                        services.active,
                        categories.name AS category_name,
                        -- capa do serviço: a imagem marcada como cover ou, na falta
                        -- dela, a mais antiga; e o total de imagens cadastradas
                        (SELECT path FROM service_images
                          WHERE service_images.services_id = services.id
                          ORDER BY service_images.cover DESC, service_images.id ASC
                          LIMIT 1) AS cover_image,
                        (SELECT COUNT(*) FROM service_images
                          WHERE service_images.services_id = services.id) AS images_count
                      FROM services
                      INNER JOIN categories ON services.categories_id = categories.id
                      WHERE services.id = :id LIMIT 1";

            $stmt = Connect::getInstance()->prepare($query);
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch();
            if (!$result) {
                $this->errorMessage = "Serviço não encontrado.";
                return false;
            }

            return (array)$result;
        } catch (PDOException $e) {
            throw $e;
            return false;
        }
    }

    /**
     * Total de serviços e soma dos valores por categoria — alimenta o gráfico do
     * dashboard e o relatório em PDF.
     *
     * Categorias sem nenhum serviço também aparecem (LEFT JOIN, total 0), para que
     * o gráfico mostre onde falta cadastro.
     *
     * @return array<int, object{category_name: string, total: int, total_value: float}>
     */
    public function statsByCategory(bool $onlyActive = true): array
    {
        try {
            $condition = $onlyActive ? "AND services.active = 1" : "";

            $query = "SELECT
                        categories.id AS category_id,
                        categories.name AS category_name,
                        COUNT(services.id) AS total,
                        SUM(CASE WHEN services.id IS NOT NULL AND CAST(services.value AS DECIMAL(12,2)) = 0 THEN 1 ELSE 0 END) AS quoted,
                        COALESCE(SUM(CAST(services.value AS DECIMAL(12,2))), 0) AS total_value
                      FROM categories
                      LEFT JOIN services
                        ON services.categories_id = categories.id {$condition}
                      WHERE categories.active = 1
                      GROUP BY categories.id, categories.name
                      ORDER BY total DESC, categories.name ASC";

            $stmt = Connect::getInstance()->query($query);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw $e;
            return [];
        }
    }

    /**
     * Serviços com o nome da categoria e a quantidade de imagens de cada um,
     * usado no relatório de catálogo em PDF.
     */
    public function selectAllForReport(bool $onlyActive = false): array
    {
        try {
            $condition = $onlyActive ? "WHERE services.active = 1" : "";

            $query = "SELECT
                        services.id,
                        services.name,
                        services.description,
                        services.value,
                        services.active,
                        categories.name AS category_name,
                        (SELECT COUNT(*) FROM service_images
                          WHERE service_images.services_id = services.id) AS images
                      FROM services
                      INNER JOIN categories ON services.categories_id = categories.id
                      {$condition}
                      ORDER BY categories.name ASC, services.name ASC";

            $stmt = Connect::getInstance()->query($query);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw $e;
            return [];
        }
    }

    // O nome do serviço é único entre os registros ATIVOS: um serviço inativado
    // não deve impedir que o mesmo nome seja cadastrado de novo.
    public function nameInUse(string $name, ?int $ignoreId = null): bool
    {
        $query = "SELECT id FROM {$this->table} WHERE name = :name AND active = 1";
        if ($ignoreId !== null) {
            $query .= " AND {$this->primaryKey} != :id";
        }

        $stmt = Connect::getInstance()->prepare($query);
        $stmt->bindValue(":name", $name);
        if ($ignoreId !== null) {
            $stmt->bindValue(":id", $ignoreId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
