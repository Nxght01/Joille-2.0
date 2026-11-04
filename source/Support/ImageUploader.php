<?php

namespace Source\Support;

use CoffeeCode\Uploader\Image;


/**
 * Upload e manipulação de imagens sobre CoffeeCode\Uploader\Image.
 *
 * Responsabilidades:
 *   - validar tipo real (mime do arquivo, não o declarado pelo navegador) e tamanho;
 *   - redimensionar/recomprimir a imagem no envio (quem faz isso é o Uploader);
 *   - devolver o caminho relativo à raiz do projeto, que é o valor guardado em
 *     `users.photo` e em `service_images.path`;
 *   - remover do disco imagens já enviadas.
 */
class ImageUploader
{
    /** Tipos aceitos — são os que CoffeeCode\Uploader\Image sabe reamostrar. */
    private const ALLOWED_TYPES = ["image/jpeg", "image/png"];

    /** Limite de 4 MB por arquivo. */
    private const MAX_SIZE = 4 * 1024 * 1024;

    /** Largura máxima em pixels; imagens maiores são redimensionadas no upload. */
    public const MAX_WIDTH = 1600;

    /** Largura usada na foto de perfil, bem menor que a das imagens do catálogo. */
    public const PROFILE_WIDTH = 600;

    private string $uploadDir;
    private string $imageDir;
    private ?string $errorMessage = null;

    public function __construct(string $uploadDir = CONF_UPLOAD_DIR, string $imageDir = CONF_UPLOAD_IMAGE_DIR)
    {
        $this->uploadDir = $uploadDir;
        $this->imageDir = $imageDir;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Envia uma imagem e devolve o caminho relativo gravado, ou null em caso de erro.
     */
    public function upload(array $file, int $width = self::MAX_WIDTH): ?string
    {
        // Upload da API de referência valida MIME real, tamanho e origem multipart.
        $receiver = new Upload(sys_get_temp_dir());
        $receiver->maxImageSize = self::MAX_SIZE;
        $receiver->allowedImageTypes = self::ALLOWED_TYPES;
        try {
            $name = $receiver->image($file);
        } catch (\RuntimeException $error) {
            $this->errorMessage = $error->getMessage();
            return null;
        }

        $temp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $name;
        $previousDir = getcwd();
        try {
            $info = getimagesize($temp);
            if (!$info || $info[0] * $info[1] > 12000000) {
                $this->errorMessage = "A imagem deve ter no máximo 12 megapixels.";
                return null;
            }
            chdir($this->projectRoot());
            $image = new Image($this->uploadDir, $this->imageDir);
            $path = $image->upload(["tmp_name" => $temp, "type" => $info["mime"]], pathinfo($name, PATHINFO_FILENAME), $width);
            if (!is_file($path) || !filesize($path)) {
                if (is_file($path)) unlink($path);
                throw new \RuntimeException("Falha de gravação.");
            }
            return $path;
        } catch (\Throwable $error) {
            $this->errorMessage = "Não foi possível processar a imagem.";
            return null;
        } finally {
            chdir($previousDir);
            unlink($temp);
        }
    }

    /**
     * Envia várias imagens de um input `multiple` e devolve os caminhos gravados.
     *
     * Um arquivo inválido desfaz o lote, inclusive os arquivos já gravados.
     *
     * @param string $inputName nome do campo em $_FILES
     * @return string[]
     */
    public function uploadMany(string $inputName, array $files, int $width = self::MAX_WIDTH): array
    {
        if (empty($files[$inputName]["name"])) {
            $this->errorMessage = "Nenhum arquivo enviado.";
            return [];
        }

        $normalized = $this->normalizeMultiple($files[$inputName]);

        $paths = [];


        foreach ($normalized as $file) {
            $path = $this->upload($file, $width);

            if ($path === null) {
                foreach ($paths as $saved) $this->remove($saved);
                return [];
            }

            $paths[] = $path;
        }

        $this->errorMessage = null;

        return $paths;
    }

    /**
     * Apaga do disco uma imagem já enviada.
     *
     * Só remove caminhos de dentro de uploads/: um valor vindo do banco que aponte
     * para fora dessa pasta é ignorado, para que nenhum registro adulterado consiga
     * apagar arquivos do projeto.
     */
    public function remove(?string $relativePath): bool
    {
        if (empty($relativePath)) {
            return false;
        }

        $root = $this->projectRoot();
        $base = realpath($root . DIRECTORY_SEPARATOR . $this->uploadDir . DIRECTORY_SEPARATOR . $this->imageDir);
        $target = realpath($root . DIRECTORY_SEPARATOR . ltrim($relativePath, "/\\"));

        if ($base === false || $target === false || !str_starts_with($target, $base . DIRECTORY_SEPARATOR)) {
            $this->errorMessage = "Arquivo fora do diretório de uploads.";
            return false;
        }

        if (!is_file($target) || !in_array(strtolower(pathinfo($target, PATHINFO_EXTENSION)), ["jpg", "jpeg", "png"], true)) {
            $this->errorMessage = "Arquivo não encontrado.";
            return false;
        }

        return @unlink($target);
    }

    /**
     * Um input `multiple` chega em $_FILES como arrays paralelos
     * (["name" => [a, b], "tmp_name" => [x, y], ...]). Aqui isso volta a ser uma
     * lista de arquivos, cada um no formato de $_FILES["campo"].
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeMultiple(array $entry): array
    {
        if (!is_array($entry["name"])) {
            return [$entry];
        }

        $files = [];

        foreach (array_keys($entry["name"]) as $index) {
            foreach (array_keys($entry) as $key) {
                $files[$index][$key] = $entry[$key][$index] ?? null;
            }
        }

        return array_values($files);
    }

    private function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
