<?php

namespace Source\Models\Faqs;

use Source\Core\Model;

class Type extends Model
{
    private ?int $id;
    private ?string $description;
    private ?int $active;

    public function __construct(?int $id = null, ?string $description = null, ?int $active = 1)
    {
        $this->id = $id;
        $this->description = $description;
        $this->active = $active;

        $this->table = 'types';
        $this->primaryKey = 'id';
        $this->fillable = ['description', 'active'];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
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
