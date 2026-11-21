<?php
namespace Source\Support;

final class ApiErrors
{
    public static function register(): void
    {
        ini_set('display_errors', '0');
        set_exception_handler(static function (\Throwable $error): void {
            $invalid = $error instanceof \JsonException || $error instanceof \InvalidArgumentException;
            if (!$invalid) error_log((string)$error);
            if (ob_get_level()) ob_clean();
            http_response_code($invalid ? 400 : 500);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'code' => $invalid ? 400 : 500,
                'type' => 'error',
                'status' => $invalid ? 'bad_request' : 'internal_server_error',
                'message' => $invalid ? 'Corpo da requisição inválido. Envie um objeto JSON com campos simples.' : 'Não foi possível concluir a operação. Tente novamente.',
                'data' => null
            ], JSON_UNESCAPED_UNICODE);
        });
    }
}
