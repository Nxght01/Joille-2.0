<?php

namespace Source\Controller;

use Source\Controller\Api;
use Source\Models\Category;

class Categories extends Api
{
    public function listAll(array $data): void
    {
        $category = new Category();
        $this->call(200, "success", "Lista de Categorias", "success")
            ->back($category->selectAll(["active = 1"]));
    }

    public function listAllAdmin(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        $category = new Category();
        $this->call(200, "success", "Lista administrativa de Categorias", "success")
            ->back($category->selectAll());
    }

    public function listById(array $data): void
    {
        if (!isset($data["categoryId"]) || empty($data["categoryId"]) || !filter_var($data["categoryId"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request", "ID da categoria é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        $category = new Category();
        if (!$category->selectById((int)$data["categoryId"]) || (!$category->getActive() && !$this->authToken(1))) {
            $this->call(404, "not_found", "Categoria não encontrada", "error")->back();
            return;
        }

        $this->call(200, "success", "Categoria encontrada", "success")->back($this->present($category));
    }

    public function listPaginator(array $data): void
    {
        if (!isset($data["page"], $data["per_page"]) ||
            !filter_var($data["page"], FILTER_VALIDATE_INT) ||
            !filter_var($data["per_page"], FILTER_VALIDATE_INT) ||
            (int)$data['page'] < 1 || (int)$data['per_page'] < 1 || (int)$data['per_page'] > 100) {
            $this->call(400, "bad_request", "Os campos page e per_page são obrigatórios e devem ser números inteiros", "error")->back();
            return;
        }

        $category = new Category();
        $response = $category->selectPaginator((int)$data["page"], (int)$data["per_page"], ["active = 1"], 'id', 'ASC');
        $this->call(200, "success", "Lista de Categorias com Paginação", "success")->back($response);
    }

    public function insert(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        if (!isset($data["name"]) || empty(trim($data["name"]))) {
            $this->call(400, "bad_request", "O campo name é obrigatório", "error")->back();
            return;
        }

        $category = new Category();
        if ($category->nameInUse($data["name"])) {
            $this->call(400, "bad_request", "Categoria já cadastrada", "error")->back();
            return;
        }

        $category = new Category(null, $data["name"], 1);
        if (!$category->insert()) {
            $this->call(500, "internal_server_error", $category->getErrorMessage(), "error")->back();
            return;
        }

        $this->call(201, "created", "Categoria cadastrada com sucesso", "success")->back($this->present($category));
    }

    public function update(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        if (!isset($data["categoryId"]) || !filter_var($data["categoryId"], FILTER_VALIDATE_INT) ||
            !isset($data["name"]) || empty(trim($data["name"]))) {
            $this->call(400, "bad_request", "Os campos name e categoryId são obrigatórios. O campo categoryId deve ser um número inteiro", "error")->back();
            return;
        }

        $categoryId = (int)$data["categoryId"];

        $category = new Category();
        if (!$category->selectById($categoryId)) {
            $this->call(404, "not_found", "Categoria não encontrada", "error")->back();
            return;
        }

        if ($category->nameInUse($data["name"], $categoryId)) {
            $this->call(400, "bad_request", "Nome já cadastrado", "error")->back();
            return;
        }

        $category->setName($data["name"]);

        if (array_key_exists("active", $data)) {
            $active = filter_var($data["active"], FILTER_VALIDATE_INT);
            if ($active === false || !in_array($active, [0, 1], true)) {
                $this->call(400, "bad_request", "O campo active deve ser 0 ou 1", "error")->back();
                return;
            }
            $category->setActive($active);
        }

        if (!$category->updateById($categoryId)) {
            $this->call(500, "internal_server_error", $category->getErrorMessage(), "error")->back();
            return;
        }

        $this->call(200, "success", "Categoria atualizada com sucesso", "success")->back($this->present($category));
    }

    public function delete(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        if (!isset($data["categoryId"]) || !filter_var($data["categoryId"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request", "ID da categoria é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        $categoryId = (int)$data["categoryId"];

        $category = new Category();
        if ($category->hasServices($categoryId)) {
            $this->call(400, "bad_request", "Categoria possui serviços vinculados e não pode ser excluída", "error")->back();
            return;
        }

        // soft delete
        if (!$category->softDeleteById($categoryId)) {
            $this->call(404, "not_found", "Categoria não encontrada ou já inativa", "error")->back();
            return;
        }

        $this->call(200, "success", "Categoria excluída com sucesso", "success")->back();
    }

    private function present(Category $category): array
    {
        return [
            "id" => $category->getId(),
            "name" => $category->getName(),
            "active" => $category->getActive()
        ];
    }
}
