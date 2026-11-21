<?php
namespace Source\Core;

/** Mantém o roteador CoffeeCode e acrescenta JSON e suporte ao servidor PHP local. */
class Router extends \CoffeeCode\Router\Router
{
    private ?array $jsonBody = null;

    public function __construct(string $projectUrl, ?string $separator = ':')
    {
        parent::__construct($projectUrl, $separator);
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $base = rtrim(parse_url($projectUrl, PHP_URL_PATH) ?: '', '/');
        $this->path = rtrim(substr($uri, strlen($base)), '/');
    }

    protected function formSpoofing(): void
    {
        $type = strtolower(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]);
        if ($type !== 'application/json' || !in_array($this->httpMethod, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            parent::formSpoofing();
            return;
        }
        if ($this->jsonBody === null) {
            $body = json_decode(file_get_contents('php://input'), false, 32, JSON_THROW_ON_ERROR);
            if (!$body instanceof \stdClass) {
                throw new \InvalidArgumentException('O corpo JSON deve ser um objeto.');
            }
            $this->jsonBody = (array)$body;
            foreach ($this->jsonBody as $value) {
                if (is_object($value) || is_array($value)) {
                    throw new \InvalidArgumentException('Os campos JSON devem conter valores simples.');
                }
            }
        }
        $this->data = $this->jsonBody;
    }
}
