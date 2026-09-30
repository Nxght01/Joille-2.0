<?php

namespace Source\Controller;

use Source\Controller\Api;
use Source\Models\Faqs\Type;

class FaqsTypes extends Api
{
    public function listAll(array $data): void
    {
        $type = new Type();
        $this->call(200, "success", "Lista de Tipos de FAQs", "success")
            ->back($type->selectAll(["active = 1"]));
    }

    public function listById(array $data): void
    {
        if (!isset($data["typeId"]) || empty($data["typeId"]) || !filter_var($data["typeId"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request", "ID do tipo é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        $type = new Type();
        if (!$type->selectById((int)$data["typeId"])) {
            $this->call(404, "not_found", "Tipo não encontrado", "error")->back();
            return;
        }

        $this->call(200, "success", "Tipo encontrado", "success")->back($this->present($type));
    }

    public function insert(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        if (!isset($data["description"]) || empty(trim($data["description"]))) {
            $this->call(400, "bad_request", "O campo description é obrigatório", "error")->back();
            return;
        }

        $type = new Type(null, $data["description"], 1);
        if (!$type->insert()) {
            $this->call(500, "internal_server_error", $type->getErrorMessage(), "error")->back();
            return;
        }

        $this->call(201, "created", "Tipo criado com sucesso", "success")->back($this->present($type));
    }

    public function update(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        if (!isset($data["typeId"]) || !filter_var($data["typeId"], FILTER_VALIDATE_INT) ||
            !isset($data["description"]) || empty(trim($data["description"]))) {
            $this->call(400, "bad_request", "Os campos description e typeId são obrigatórios. O campo typeId deve ser um número inteiro", "error")->back();
            return;
        }

        $type = new Type();
        if (!$type->selectById((int)$data["typeId"])) {
            $this->call(404, "not_found", "Tipo não encontrado", "error")->back();
            return;
        }

        $type->setDescription($data["description"]);
        if (!$type->updateById((int)$data["typeId"])) {
            $this->call(500, "internal_server_error", $type->getErrorMessage(), "error")->back();
            return;
        }

        $this->call(200, "success", "Tipo atualizado com sucesso", "success")->back($this->present($type));
    }

    public function delete(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        if (!isset($data["typeId"]) || !filter_var($data["typeId"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request", "ID do tipo é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        $type = new Type();
        if (!$type->softDeleteById((int)$data["typeId"])) {
            $this->call(404, "not_found", "Tipo não encontrado ou já inativo", "error")->back();
            return;
        }

        $this->call(200, "success", "Tipo removido com sucesso", "success")->back();
    }

    private function present(Type $type): array
    {
        return [
            "id" => $type->getId(),
            "description" => $type->getDescription(),
            "active" => $type->getActive()
        ];
    }
}
