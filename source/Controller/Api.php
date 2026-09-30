<?php

namespace Source\Controller;

use Source\Models\User;
use Source\Core\JWTToken;

class Api
{

    protected int $userAuthId = 0;

    protected array $response = [];

    // Preenchidos por authToken() quando a autenticação falha, para que
    // authFail() devolva 401 (sem sessão) ou 403 (sessão sem permissão).
    protected int $authFailCode = 401;
    protected string $authFailStatus = "unauthorized";
    protected string $authFailMessage = "Usuário não está autenticado (sem token ou token inválido).";

    public function authToken (int $typeId): bool
    {

        $header = getallheaders();

        $token = $header["token"] ?? $header['Authorization'] ?? $header['authorization'] ?? null;

        if(!$token){
            return $this->denyUnauthenticated("Usuário não está autenticado (sem token).");
        }

        if(str_starts_with($token, 'Bearer ')){
            $token = substr($token, 7);
        }

        $jwt = new JWTToken();

        $jwtToken = $jwt->decode($token);

        if(!$jwtToken){
            return $this->denyUnauthenticated("Token inválido ou expirado.");
        }

        if(empty($jwtToken->data->id) || empty($jwtToken->data->email)){
            return $this->denyUnauthenticated("Token inválido ou expirado.");
        }

        // Token válido, porém emitido para outro perfil de acesso.
        $tokenTypeId = $jwtToken->data->type_id ?? null;
        if ($tokenTypeId !== null && (int)$tokenTypeId !== $typeId) {
            return $this->denyForbidden();
        }

        // Token válido e do perfil correto, mas a conta foi removida ou inativada:
        // a sessão não vale mais, então o cliente precisa autenticar de novo.
        $user = new User();
        if(!$user->permissionVerify($jwtToken->data->email, $typeId)){
            return $this->denyUnauthenticated("Sessão inválida: conta inexistente ou inativa.");
        }

        $this->userAuthId = $jwtToken->data->id;

        return true;

    }

    /**
     * Emite a resposta da falha de autenticação registrada por authToken().
     */
    protected function authFail (): void
    {
        $this->call($this->authFailCode, $this->authFailStatus, $this->authFailMessage, "error")->back();
    }

    private function denyUnauthenticated (string $message): bool
    {
        $this->authFailCode = 401;
        $this->authFailStatus = "unauthorized";
        $this->authFailMessage = $message;
        return false;
    }

    private function denyForbidden (): bool
    {
        $this->authFailCode = 403;
        $this->authFailStatus = "forbidden";
        $this->authFailMessage = "Usuário autenticado, mas sem permissão para acessar este recurso.";
        return false;
    }

    protected function call (int $code, ?string $status = null, ?string $message = null, ?string $type = null): Api
    {
        http_response_code($code);
        if(!empty($status)){
            $this->response = [
                "code" => $code,
                "type" => $type,
                "status" => $status,
                "message" => (!empty($message) ? $message : null)
            ];
        }
        return $this;
    }

    protected function back(object | array $data = null): Api
    {
        header('Content-Type: application/json');
        // Em caso de erro o contrato prevê "data": null
        $this->response["data"] = $data ?: null;
        echo json_encode($this->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $this;
    }

}
