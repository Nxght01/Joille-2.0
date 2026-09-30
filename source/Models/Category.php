<?php

namespace Source\Models;

use PDO;
use Source\Core\Model;
use Source\Core\Connect;

class Category extends Model
{
    private ?int $id;
    private ?string $name;
    private ?int $active;

    public function __construct(?int $id = null, ?string $name = null, ?int $active = 1)
    {
        $this->id = $id;
        $this->name = $name;
        $this->active = $active;

        $this->table = 'categories'; // nome da tabela do banco
        $this->primaryKey = 'id'; // nome da chave primária da tabela
        $this->fillable = ['name', 'active']; // camelCase
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getActive(): ?int
    {
        return $this->active;
    }

    public function setActive(int $active): void
    {
        $this->active = $active;
    }

    // O nome da categoria é único entre os registros ATIVOS: uma categoria inativada
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

    // Impede a exclusão de uma categoria que ainda possui serviços ATIVOS vinculados.
    // Serviços já inativados não bloqueiam a exclusão lógica da categoria.
    public function hasServices(int $id): bool
    {
        $stmt = Connect::getInstance()->prepare("SELECT id FROM services WHERE categories_id = :id AND active = 1");
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
