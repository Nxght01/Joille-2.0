<?php

namespace Source\Support;

use CoffeeCode\Uploader\Image;
use Exception;

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
        $mime = $this->validate($file);

        if ($mime === null) {
            return null;
        }

        // O Uploader decide a extensão a partir de $file["type"]; usar o mime real
        // evita que um tipo declarado errado gere um arquivo com extensão inválida.
        $file["type"] = $mime;

        // O CoffeeCode\Uploader resolve os caminhos a partir do diretório de trabalho atual,
        // que é o do index.php da API; por isso a raiz do projeto é fixada aqui.
        $previousDir = getcwd();
        chdir($this->projectRoot());

        try {
            $image = new Image($this->uploadDir, $this->imageDir);
            return $image->upload($file, uniqid(md5((string)time())), $width);
        } catch (Exception $exception) {
            $this->errorMessage = "Erro ao enviar a imagem: " . $exception->getMessage();
            return null;
        } finally {
            chdir($previousDir);
        }
    }

    /**
     * Envia várias imagens de um input `multiple` e devolve os caminhos gravados.
     *
     * Um arquivo inválido não interrompe os demais: ele é apenas ignorado e o
     * motivo fica em getErrorMessage(), para o controlador reportar ao usuário.
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
        $errors = [];

        foreach ($normalized as $file) {
            $path = $this->upload($file, $width);

            if ($path === null) {
                $name = $file["name"] ?? "arquivo";
                $errors[] = "{$name}: {$this->errorMessage}";
                continue;
            }

            $paths[] = $path;
        }

        $this->errorMessage = empty($errors) ? null : implode(" | ", $errors);

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
        $base = realpath($root . DIRECTORY_SEPARATOR . $this->uploadDir);
        $target = realpath($root . DIRECTORY_SEPARATOR . ltrim($relativePath, "/\\"));

        if ($base === false || $target === false || !str_starts_with($target, $base)) {
            $this->errorMessage = "Arquivo fora do diretório de uploads.";
            return false;
        }

        if (!is_file($target)) {
            $this->errorMessage = "Arquivo não encontrado.";
            return false;
        }

        return unlink($target);
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

    /**
     * Valida presença, tipo real e tamanho do arquivo.
     * @return string|null o mime válido, ou null quando o arquivo é recusado
     */
    private function validate(array $file): ?string
    {
        if (empty($file["name"]) || empty($file["tmp_name"])) {
            $this->errorMessage = "Nenhum arquivo enviado.";
            return null;
        }

        if (!empty($file["error"])) {
            $this->errorMessage = "Falha no envio do arquivo (código {$file["error"]}).";
            return null;
        }

        if (($file["size"] ?? 0) > self::MAX_SIZE) {
            $limit = (int)(self::MAX_SIZE / 1024 / 1024);
            $this->errorMessage = "A imagem deve ter no máximo {$limit} MB.";
            return null;
        }

        // O tipo declarado pelo navegador é escolhido pelo cliente; o real vem do arquivo.
        $mime = @mime_content_type($file["tmp_name"]) ?: (string)($file["type"] ?? "");

        if ($mime === "image/jpg") {
            $mime = "image/jpeg";
        }

        if (!in_array($mime, self::ALLOWED_TYPES, true)) {
            $this->errorMessage = "Por favor, envie uma imagem do tipo JPG, JPEG ou PNG.";
            return null;
        }

        return $mime;
    }

    private function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
