<?php

namespace Source\Models\Store;

use Source\Core\Model;

class Product extends Model
{
    private ?int $id;
    private ?int $categoriesId;
    private ?string $name;
    private ?float $value;
    private ?int $quantity;
    private ?string $description;
    private ?string $url;
    private ?int $active;

    public function __construct(
        ?int $id = null,
        ?int $categoriesId = null,
        ?string $name = null,
        ?float $value = null,
        ?int $quantity = null,
        ?string $description = null,
        ?string $url = null,
        ?int $active = 1
    ) {
        $this->id = $id;
        $this->categoriesId = $categoriesId;
        $this->name = $name;
        $this->value = $value;
        $this->quantity = $quantity;
        $this->description = $description;
        $this->url = $url;
        $this->active = $active;

        $this->table = 'products'; // nome da tabela do banco
        $this->primaryKey = 'id'; // nome da chave primária da tabela
        $this->fillable = ['categoriesId', 'name', 'value', 'quantity', 'description', 'url', 'active']; // camelCase
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

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): void
    {
        $this->value = $value;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getActive(): ?int
    {
        return $this->active;
    }

    public function setActive(int $active): void
    {
        $this->active = $active;
    }
}
