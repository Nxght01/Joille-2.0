<?php

namespace Source\Controller;

use Source\Core\Connect;
use Source\Models\Service;
use Source\Models\ServiceImage;
use Source\Support\ImageUploader;

/**
 * Imagens da entidade principal (Etapa 07).
 *
 * A listagem é pública — a área pública precisa exibir as fotos do catálogo.
 * Enviar, marcar capa e excluir exigem token de administrador (type_id = 1).
 */
class ServiceImages extends Api
{
    /** Teto de imagens por serviço, para não deixar o catálogo crescer sem limite. */
    private const MAX_PER_SERVICE = 10;

    public function listByService(array $data): void
    {
        $serviceId = $this->serviceId($data);

        if ($serviceId === null) {
            return;
        }

        $images = new ServiceImage();
        if (!(new Service())->selectById($serviceId)) {
            $this->call(404, "not_found", "Serviço não encontrado.", "error")->back();
            return;
        }
        $rows = $images->selectByService($serviceId);
        if ($images->getErrorMessage()) {
            $this->call(500, "internal_server_error", "Não foi possível carregar a galeria.", "error")->back();
            return;
        }
        $this->call(200, "success", "Imagens do serviço", "success")
            ->back(array_map(fn($row) => $this->present($row), $rows));
    }

    public function insert(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        $serviceId = $this->serviceId($data);

        if ($serviceId === null) {
            return;
        }

        $service = new Service();
        if (!$service->selectById($serviceId)) {
            $this->call(404, "not_found", "Serviço não encontrado", "error")->back();
            return;
        }

        if (empty($_FILES["images"]["name"])) {
            $this->call(400, "bad_request",
                "Envie ao menos uma imagem no campo `images` (JPG, JPEG ou PNG, até 4 MB cada).",
                "error")->back();
            return;
        }

        $model = new ServiceImage();
        $db = Connect::getInstance();
        $uploader = new ImageUploader();
        $paths = [];
        $inserted = [];
        try {
            $db->beginTransaction();
            if (!$model->lockActiveService($serviceId)) {
                $db->rollBack();
                $this->call(404, "not_found", "Serviço inativo ou inexistente.", "error")->back();
                return;
            }
            $already = $model->countByService($serviceId);
            if ($model->getErrorMessage()) throw new \RuntimeException();
            $count = count((array)$_FILES["images"]["name"]);
            $caption = trim((string)($data["caption"] ?? "")) ?: null;
            if ($count + $already > self::MAX_PER_SERVICE || mb_strlen($caption ?? "") > 255) {
                $db->rollBack();
                $this->call(400, "bad_request", "Máximo de 10 imagens por serviço e 255 caracteres na legenda.", "error")->back();
                return;
            }
            $paths = $uploader->uploadMany("images", $_FILES);
            if (!$paths) {
                $db->rollBack();
                $this->call(400, "bad_request", $uploader->getErrorMessage(), "error")->back();
                return;
            }
            foreach ($paths as $index => $path) {
                $cover = (int)($already === 0 && $index === 0);
                $image = new ServiceImage(null, $serviceId, $path, $caption, $cover);
                if (!$image->insert()) throw new \RuntimeException();
                $inserted[] = ["id" => $image->getId(), "services_id" => $serviceId, "path" => $path,
                    "caption" => $caption, "cover" => $cover, "url" => url($path)];
            }
            $db->commit();
        } catch (\Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            foreach ($paths as $path) $uploader->remove($path);
            $this->call(500, "internal_server_error", "Não foi possível salvar o lote de imagens.", "error")->back();
            return;
        }

        $total = count($inserted);
        $message = $total === 1 ? "Imagem enviada com sucesso" : "{$total} imagens enviadas com sucesso";

        $this->call(201, "created", $message, "success")->back($inserted);
    }

    public function setCover(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        $serviceId = $this->serviceId($data);
        $imageId = $this->imageId($data);

        if ($serviceId === null || $imageId === null) {
            return;
        }

        $images = new ServiceImage();

        if (!$images->markAsCover($imageId, $serviceId)) {
            $this->call(404, "not_found", $images->getErrorMessage(), "error")->back();
            return;
        }

        $this->call(200, "success", "Capa definida com sucesso", "success")->back([
            "id" => $imageId,
            "services_id" => $serviceId,
            "cover" => 1
        ]);
    }

    public function delete(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        $serviceId = $this->serviceId($data);
        $imageId = $this->imageId($data);

        if ($serviceId === null || $imageId === null) {
            return;
        }

        $images = new ServiceImage();
        $image = $images->selectByIdAndService($imageId, $serviceId);

        if (!$image) {
            $this->call(404, "not_found", $images->getErrorMessage(), "error")->back();
            return;
        }

        if (!$images->deleteById($imageId)) {
            $this->call(500, "internal_server_error", $images->getErrorMessage(), "error")->back();
            return;
        }

        // O arquivo só é apagado depois que o registro sai do banco: se a ordem
        // fosse a inversa, uma falha no DELETE deixaria um registro sem imagem.
        if (!(new ImageUploader())->remove($image->path)) {
            $this->call(500, "internal_server_error", "O registro foi removido, mas o arquivo não pôde ser apagado.", "error")->back();
            return;
        }

        // Sem capa, a imagem mais antiga que restou assume o posto.
        if ((int)$image->cover === 1) {
            $remaining = $images->selectByService($serviceId);
            if (!empty($remaining)) {
                $images->markAsCover((int)$remaining[0]->id, $serviceId);
            }
        }

        $this->call(200, "success", "Imagem excluída com sucesso", "success")->back();
    }

    private function present(object $row): array
    {
        return [
            "id" => (int)$row->id,
            "services_id" => (int)$row->services_id,
            "path" => $row->path,
            "url" => url($row->path),
            "caption" => $row->caption,
            "cover" => (int)$row->cover,
            "created_at" => $row->created_at ?? null
        ];
    }

    /** Valida o serviceId da rota; devolve null e já responde 400 quando inválido. */
    private function serviceId(array $data): ?int
    {
        if (empty($data["serviceId"]) || !filter_var($data["serviceId"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request",
                "ID do serviço é obrigatório e deve ser um número inteiro", "error")->back();
            return null;
        }

        return (int)$data["serviceId"];
    }

    private function imageId(array $data): ?int
    {
        if (empty($data["imageId"]) || !filter_var($data["imageId"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request",
                "ID da imagem é obrigatório e deve ser um número inteiro", "error")->back();
            return null;
        }

        return (int)$data["imageId"];
    }
}
