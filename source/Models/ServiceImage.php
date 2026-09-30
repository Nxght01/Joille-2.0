<?php

namespace Source\Models;

use PDO;
use PDOException;
use Source\Core\Connect;
use Source\Core\Model;

/**
 * Imagem da entidade principal (`service_images`).
 *
 * Um serviço tem N imagens e no máximo uma capa (`cover = 1`), que é a usada nas
 * listagens da área pública e do painel.
 */
class ServiceImage extends Model
{
    private ?int $id;
    private ?int $servicesId;
    private ?string $path;
    private ?string $caption;
    private ?int $cover;

    public function __construct(
        ?int $id = null,
        ?int $servicesId = null,
        ?string $path = null,
        ?string $caption = null,
        ?int $cover = 0
    ) {
        $this->id = $id;
        $this->servicesId = $servicesId;
        $this->path = $path;
        $this->caption = $caption;
        $this->cover = $cover;

        $this->table = 'service_images';
        $this->primaryKey = 'id';
        $this->fillable = ['servicesId', 'path', 'caption', 'cover'];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getServicesId(): ?int
    {
        return $this->servicesId;
    }

    public function setServicesId(?int $servicesId): void
    {
        $this->servicesId = $servicesId;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): void
    {
        $this->caption = $caption;
    }

    public function getCover(): ?int
    {
        return $this->cover;
    }

    public function setCover(?int $cover): void
    {
        $this->cover = $cover;
    }

    /** Imagens de um serviço, capa primeiro. */
    public function selectByService(int $serviceId): array
    {
        try {
            $query = "SELECT id, services_id, path, caption, cover, created_at
                      FROM {$this->table}
                      WHERE services_id = :id
                      ORDER BY cover DESC, id ASC";

            $stmt = Connect::getInstance()->prepare($query);
            $stmt->bindValue(":id", $serviceId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
            return [];
        }
    }

    /**
     * Capa de cada serviço, indexada por services_id.
     * Evita uma consulta por serviço ao montar as listagens.
     *
     * @return array<int, string> services_id => path
     */
    public function coversByService(): array
    {
        try {
            // A ordenação faz o último registro de cada serviço ser o desejado, já
            // que a atribuição no laço sobrescreve: `cover ASC` deixa a capa
            // explícita no fim e, entre as demais, `id DESC` deixa a mais antiga.
            $query = "SELECT services_id, path
                      FROM {$this->table}
                      ORDER BY services_id ASC, cover ASC, id DESC";

            $stmt = Connect::getInstance()->query($query);

            $covers = [];
            foreach ($stmt->fetchAll() as $row) {
                $covers[(int)$row->services_id] = $row->path;
            }

            return $covers;
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
            return [];
        }
    }

    /** Total de imagens já cadastradas para um serviço. */
    public function countByService(int $serviceId): int
    {
        try {
            $stmt = Connect::getInstance()->prepare(
                "SELECT COUNT(*) AS total FROM {$this->table} WHERE services_id = :id"
            );
            $stmt->bindValue(":id", $serviceId, PDO::PARAM_INT);
            $stmt->execute();

            return (int)$stmt->fetch()->total;
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
            return 0;
        }
    }

    /**
     * Marca uma imagem como capa e desmarca as outras do mesmo serviço.
     * @return bool false quando a imagem não pertence ao serviço informado
     */
    public function markAsCover(int $imageId, int $serviceId): bool
    {
        try {
            $connection = Connect::getInstance();

            $check = $connection->prepare(
                "SELECT id FROM {$this->table} WHERE id = :id AND services_id = :service"
            );
            $check->bindValue(":id", $imageId, PDO::PARAM_INT);
            $check->bindValue(":service", $serviceId, PDO::PARAM_INT);
            $check->execute();

            if ($check->rowCount() === 0) {
                $this->errorMessage = "Imagem não encontrada para este serviço.";
                return false;
            }

            $reset = $connection->prepare("UPDATE {$this->table} SET cover = 0 WHERE services_id = :service");
            $reset->bindValue(":service", $serviceId, PDO::PARAM_INT);
            $reset->execute();

            $set = $connection->prepare("UPDATE {$this->table} SET cover = 1 WHERE id = :id");
            $set->bindValue(":id", $imageId, PDO::PARAM_INT);
            $set->execute();

            return true;
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
            return false;
        }
    }

    /** Busca uma imagem garantindo que ela pertence ao serviço informado. */
    public function selectByIdAndService(int $imageId, int $serviceId): object|false
    {
        try {
            $stmt = Connect::getInstance()->prepare(
                "SELECT * FROM {$this->table} WHERE id = :id AND services_id = :service LIMIT 1"
            );
            $stmt->bindValue(":id", $imageId, PDO::PARAM_INT);
            $stmt->bindValue(":service", $serviceId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch();

            if (!$result) {
                $this->errorMessage = "Imagem não encontrada para este serviço.";
                return false;
            }

            return $result;
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
            return false;
        }
    }
}
