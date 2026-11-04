<?php

namespace Source\Controller;

use Source\Controller\Api;
use Source\Models\Service;

class Services extends Api
{
    public function listAll(array $data): void
    {
        $service = new Service();
        $this->call(200, "success", "Lista de Serviços", "success")
            ->back($service->selectAllWithCategory(["services.active = 1"]));
    }

    public function listAllAdmin(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        $service = new Service();
        $this->call(200, "success", "Lista administrativa de Serviços", "success")
            ->back($service->selectAllWithCategory());
    }

    public function listById(array $data): void
    {
        if (!isset($data["serviceId"]) || empty($data["serviceId"]) || !filter_var($data["serviceId"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request", "ID do serviço é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        $service = new Service();
        $response = $service->selectByIdWithCategory((int)$data["serviceId"]);

        if (!$response) {
            $this->call(404, "not_found", "Serviço não encontrado", "error")->back();
            return;
        }

        $this->call(200, "success", "Serviço encontrado", "success")->back($response);
    }

    public function listByCategory(array $data): void
    {
        if (!isset($data["categoryId"]) || empty($data["categoryId"]) || !filter_var($data["categoryId"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request", "ID da categoria é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        $service = new Service();
        $categoryId = (int)$data["categoryId"];
        $this->call(200, "success", "Lista de Serviços por categoria", "success")
            ->back($service->selectAllWithCategory(["services.active = 1", "services.categories_id = {$categoryId}"]));
    }

    public function listPaginator(array $data): void
    {
        if (!isset($data["page"], $data["per_page"]) ||
            !filter_var($data["page"], FILTER_VALIDATE_INT) ||
            !filter_var($data["per_page"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request", "Os campos page e per_page são obrigatórios e devem ser números inteiros", "error")->back();
            return;
        }

        $service = new Service();
        $response = $service->selectPaginator((int)$data["page"], (int)$data["per_page"], [], 'id', 'ASC');
        $this->call(200, "success", "Lista de Serviços com Paginação", "success")->back($response);
    }

    public function insert(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        if (!$this->validate($data)) {
            $this->call(400, "bad_request", "Os campos categories_id, name, description e value são obrigatórios. O campo categories_id deve ser um número inteiro", "error")->back();
            return;
        }

        $service = new Service();
        if ($service->nameInUse($data["name"])) {
            $this->call(400, "bad_request", "Serviço já cadastrado", "error")->back();
            return;
        }

        $service = new Service(
            null,
            (int)$data["categories_id"],
            $data["name"],
            $data["description"],
            $data["value"],
            1
        );

        if (!$service->insert()) {
            $this->call(500, "internal_server_error", $service->getErrorMessage(), "error")->back();
            return;
        }

        $this->call(201, "created", "Serviço cadastrado com sucesso", "success")
            ->back($this->present($service));
    }

    public function update(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        if (!isset($data["serviceId"]) || !filter_var($data["serviceId"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request", "O campo serviceId é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        $serviceId = (int)$data["serviceId"];

        $service = new Service();
        if (!$service->selectById($serviceId)) {
            $this->call(404, "not_found", "Serviço não encontrado", "error")->back();
            return;
        }

        if (isset($data["categories_id"]) && !empty($data["categories_id"])) {
            if (!filter_var($data["categories_id"], FILTER_VALIDATE_INT)) {
                $this->call(400, "bad_request", "O campo categories_id deve ser um número inteiro", "error")->back();
                return;
            }
            $service->setCategoriesId((int)$data["categories_id"]);
        }

        if (isset($data["name"]) && !empty(trim($data["name"]))) {
            if ($service->nameInUse($data["name"], $serviceId)) {
                $this->call(400, "bad_request", "Nome já cadastrado", "error")->back();
                return;
            }
            $service->setName($data["name"]);
        }

        if (isset($data["description"]) && !empty(trim($data["description"]))) {
            $service->setDescription($data["description"]);
        }

        if (isset($data["value"]) && trim((string)$data["value"]) !== "") {
            $service->setValue((string)$data["value"]);
        }

        if (array_key_exists("active", $data)) {
            $active = filter_var($data["active"], FILTER_VALIDATE_INT);
            if ($active === false || !in_array($active, [0, 1], true)) {
                $this->call(400, "bad_request", "O campo active deve ser 0 ou 1", "error")->back();
                return;
            }
            $service->setActive($active);
        }

        if (!$service->updateById($serviceId)) {
            $this->call(500, "internal_server_error", $service->getErrorMessage(), "error")->back();
            return;
        }

        $this->call(200, "success", "Serviço atualizado com sucesso", "success")
            ->back($service->selectByIdWithCategory($serviceId) ?: $this->present($service));
    }

    public function delete(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        if (!isset($data["serviceId"]) || !filter_var($data["serviceId"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request", "ID do serviço é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        try {
            $paths = (new \Source\Models\ServiceImage())->deactivateService((int)$data["serviceId"]);
        } catch (\Throwable $error) {
            $this->call(500, "internal_server_error", "Não foi possível inativar o serviço.", "error")->back();
            return;
        }
        if ($paths === false) {
            $this->call(404, "not_found", "Serviço não encontrado ou já inativo", "error")->back();
            return;
        }
        $storage = new \Source\Support\ImageUploader();
        $clean = true;
        foreach ($paths as $path) {
            if (!$storage->remove($path)) $clean = false;
        }
        if (!$clean) {
            $this->call(500, "internal_server_error", "Serviço inativado; a limpeza dos arquivos não foi concluída.", "error")->back();
            return;
        }
        $this->call(200, "success", "Serviço excluído com sucesso", "success")->back();
    }

    private function present(Service $service): array
    {
        return [
            "id" => $service->getId(),
            "categories_id" => $service->getCategoriesId(),
            "name" => $service->getName(),
            "description" => $service->getDescription(),
            "value" => $service->getValue(),
            "active" => $service->getActive()
        ];
    }

    private function validate(array $data): bool
    {
        if (!isset($data["categories_id"], $data["name"], $data["description"], $data["value"]) ||
            empty($data["categories_id"]) || empty(trim($data["name"])) ||
            empty(trim($data["description"])) || trim((string)$data["value"]) === "" ||
            !filter_var($data["categories_id"], FILTER_VALIDATE_INT)) {
            return false;
        }
        return true;
    }
}
