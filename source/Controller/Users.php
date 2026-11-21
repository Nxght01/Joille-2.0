<?php

namespace Source\Controller;

use Source\Models\User;
use Source\Support\ImageUploader;

class Users extends Api
{
    public function register (array $data): void
    {
        if(!isset($data['password']) || empty($data['password'])) {
            $this->call(400,
                "bad_request",
                "A senha é obrigatória.",
                "error")->back();
            return;
        }

        if(strlen($data['password']) < 8) {
            $this->call(400,
                "bad_request",
                "A senha deve ter no mínimo 8 caracteres.",
                "error")->back();
            return;
        }

        if(!$this->validateNameEmail($data)){
            $this->call(400,
                "bad_request",
                "Nome e e-mail são obrigatórios. O e-mail deve ser válido.",
                "error")->back();
            return;
        }

        $user = new User(
            null,
            2,
            $data['name'],
            $data['email'],
            $data['password'],
            null,
            null,
            null,
            1
        );

        if(!$user->insert()) {
            $this->failedInsert($user);
            return;
        }

        $response = [
            "id" => $user->getId(),
            "name" => $user->getName(),
            "email" => $user->getEmail()
        ];

        $this->call(201,"created","Usuário inserido com sucesso","success")->back($response);
    }

    public function registerAdmin (array $data): void
    {
        if(!$this->authToken (1)){
            $this->authFail();
            return;
        }

        if(!isset($data['password']) || strlen($data['password']) < 8) {
            $this->call(400,
                "bad_request",
                "A senha é obrigatória e deve ter no mínimo 8 caracteres.",
                "error")->back();
            return;
        }

        if(!$this->validateNameEmail($data)){
            $this->call(400,
                "bad_request",
                "Nome e e-mail são obrigatórios. O e-mail deve ser válido.",
                "error")->back();
            return;
        }

        // O admin pode cadastrar tanto outro administrador quanto um cliente
        $typeId = 1;
        if(isset($data["type_id"])){
            $typeId = (int)$data["type_id"];
            if(!in_array($typeId, [1, 2], true)){
                $this->call(400, "bad_request", "O campo type_id deve ser 1 (admin) ou 2 (cliente)", "error")->back();
                return;
            }
        }

        $user = new User(
            null,
            $typeId,
            $data['name'],
            $data['email'],
            $data['password'],
            null,
            null,
            null,
            1
        );

        if(!$user->insert()) {
            $this->failedInsert($user);
            return;
        }

        $response = [
            "id" => $user->getId(),
            "name" => $user->getName(),
            "email" => $user->getEmail(),
            "type_id" => $user->getTypeId()
        ];

        $this->call(201,"created","Administrador inserido com sucesso","success")->back($response);
    }

    public function auth (array $data): void
    {
        if(!$this->validateCredentials($data)) {
            $this->call(
                400,
                "bad_request",
                "E-mail e senha são obrigatórios. O e-mail deve ser válido.",
                "error")->back();
            return;
        }

        $user = new User();
        if(!$user->login($data['email'], $data['password'])) {
            $this->call(
                401,
                "unauthorized",
                $user->getErrorMessage(),
                "error")->back();
            return;
        }

        $response = [
            "id" => $user->getId(),
            "name" => $user->getName(),
            "email" => $user->getEmail(),
            "photo" => $user->getPhoto(),
            "type_id" => $user->getTypeId(),
            "token" => $user->getToken(),
        ];

        $this->call(
            200,
            "success",
            "Usuário logado com sucesso",
            "success")->back($response);
    }

    public function authAdmin (array $data): void
    {
        if(!$this->validateCredentials($data)) {
            $this->call(
                400,
                "bad_request",
                "E-mail e senha são obrigatórios. O e-mail deve ser válido.",
                "error")->back();
            return;
        }

        $user = new User();
        if(!$user->login($data['email'], $data['password'], 1)) {
            $this->call(
                401,
                "unauthorized",
                $user->getErrorMessage(),
                "error")->back();
            return;
        }

        $response = [
            "id" => $user->getId(),
            "name" => $user->getName(),
            "email" => $user->getEmail(),
            "photo" => $user->getPhoto(),
            "type_id" => $user->getTypeId(),
            "token" => $user->getToken(),
        ];

        $this->call(
            200,
            "success",
            "Usuário logado com sucesso",
            "success")->back($response);
    }

    public function tokenValidate (array $data): void
    {
        if(!$this->authToken ([1, 2])){
            $this->authFail();
            return;
        }

        $this->call(200,"success","Token válido","success")->back(["id" => $this->userAuthId]);
    }

    public function me (array $data): void
    {
        if(!$this->authToken ([1, 2])){
            $this->authFail();
            return;
        }

        $user = new User();
        if(!$user->selectById($this->userAuthId)){
            $this->call(404, "not_found", "Usuário não encontrado", "error")->back();
            return;
        }

        $response = [
            "id" => $user->getId(),
            "name" => $user->getName(),
            "email" => $user->getEmail(),
            "photo" => $user->getPhoto(),
            "type_id" => $user->getTypeId(),
            "active" => $user->getActive(),
            "address" => $user->getAddress(), "phone" => $user->getPhone()
        ];

        $this->call(200,"success","Usuário autenticado","success")->back($response);
    }

    public function update (array $data): void
    {
        if(!$this->authToken ([1, 2])){
            $this->authFail();
            return;
        }

        if(!$this->validateNameEmail($data)){
            $this->call(400,
                "bad_request",
                "Nome e e-mail são obrigatórios. O e-mail deve ser válido.",
                "error")->back();
            return;
        }

        $user = new User();
        if($user->emailInUse($data["email"], $this->userAuthId)){
            $this->call(400, "bad_request", "E-mail já cadastrado", "error")->back();
            return;
        }

        $user->setName($data["name"]);
        $user->setEmail($data["email"]);
        foreach (['address' => 255, 'phone' => 30] as $field => $limit) {
            if (isset($data[$field])) {
                if (!is_string($data[$field]) || mb_strlen($data[$field]) > $limit) {
                    $this->call(400, 'bad_request', 'Endereço ou telefone inválido.', 'error')->back();
                    return;
                }
                $user->{'set' . ucfirst($field)}(trim($data[$field]));
            }
        }

        if(!$user->updateById($this->userAuthId)){
            $this->call(500, "internal_server_error", $user->getErrorMessage(), "error")->back();
            return;
        }

        $response = [
            "id" => $this->userAuthId,
            "name" => $user->getName(),
            "email" => $user->getEmail(),
            "type_id" => $this->userAuthType,
            "address" => $user->getAddress(), "phone" => $user->getPhone()
        ];

        $this->call(200,"success","Usuário atualizado com sucesso","success")->back($response);
    }

    public function updateAdmin (array $data): void
    {
        if(!$this->authToken (1)){
            $this->authFail();
            return;
        }

        if(!isset($data["userId"]) || !filter_var($data["userId"], FILTER_VALIDATE_INT)){
            $this->call(400, "bad_request", "O campo userId é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        if(!$this->validateNameEmail($data)){
            $this->call(400,
                "bad_request",
                "Nome e e-mail são obrigatórios. O e-mail deve ser válido.",
                "error")->back();
            return;
        }

        $userId = (int)$data["userId"];

        $user = new User();
        if(!$user->selectById($userId)){
            $this->call(404, "not_found", "Usuário não encontrado", "error")->back();
            return;
        }

        if($user->emailInUse($data["email"], $userId)){
            $this->call(400, "bad_request", "E-mail já cadastrado", "error")->back();
            return;
        }

        $user->setName($data["name"]);
        $user->setEmail($data["email"]);
        $user->setPassword(null); // o hash carregado pelo selectById não pode ser regravado

        // A senha só é trocada quando o admin realmente preenche o campo
        if(!empty($data["password"])){
            if(strlen($data["password"]) < 8){
                $this->call(400, "bad_request", "A senha deve ter no mínimo 8 caracteres.", "error")->back();
                return;
            }
            $user->setPassword(password_hash($data["password"], PASSWORD_DEFAULT));
        }

        if(isset($data["type_id"])){
            $typeId = filter_var($data['type_id'], FILTER_VALIDATE_INT);
            if (!in_array($typeId, [1, 2], true)) {
                $this->call(400, 'bad_request', 'O perfil deve ser 1 ou 2.', 'error')->back();
                return;
            }
            $user->setTypeId($typeId);
        }

        if(array_key_exists("active", $data)){
            $active = filter_var($data["active"], FILTER_VALIDATE_INT);
            if($active === false || !in_array($active, [0, 1], true)){
                $this->call(400, "bad_request", "O campo active deve ser 0 ou 1", "error")->back();
                return;
            }
            $user->setActive($active);
        }

        if ($userId === $this->userAuthId && ($user->getTypeId() !== 1 || !$user->getActive())) {
            $this->call(400, 'bad_request', 'Você não pode remover seu próprio acesso administrativo.', 'error')->back();
            return;
        }
        if(!$user->updateById($userId)){
            $this->call(500, "internal_server_error", $user->getErrorMessage(), "error")->back();
            return;
        }

        $response = [
            "id" => $userId,
            "name" => $user->getName(),
            "email" => $user->getEmail(),
            "type_id" => $user->getTypeId(),
            "active" => $user->getActive()
        ];

        $this->call(200,"success","Usuário atualizado com sucesso","success")->back($response);
    }

    public function updatePassword (array $data): void
    {
        if(!$this->authToken ([1, 2])){
            $this->authFail();
            return;
        }

        if(!isset($data["password"], $data["newPassword"], $data["confirmNewPassword"]) ||
            empty($data["password"]) || empty($data["newPassword"])){
            $this->call(400, "bad_request", "Os campos password, newPassword e confirmNewPassword são obrigatórios", "error")->back();
            return;
        }

        if($data["newPassword"] !== $data["confirmNewPassword"]){
            $this->call(400, "bad_request", "As senhas não conferem", "error")->back();
            return;
        }

        if(strlen($data["newPassword"]) < 8){
            $this->call(400, "bad_request", "A nova senha deve ter no mínimo 8 caracteres", "error")->back();
            return;
        }

        $user = new User();
        if(!$user->updatePassword($this->userAuthId, $data["password"], $data["newPassword"])){
            $this->call(400, "bad_request", $user->getErrorMessage(), "error")->back();
            return;
        }

        $this->call(200,"success","Senha atualizada com sucesso","success")->back();
    }

    public function updatePhoto (array $data): void
    {
        if(!$this->authToken ([1, 2])){
            $this->authFail();
            return;
        }

        $photo = (!empty($_FILES["photo"]["name"]) ? $_FILES["photo"] : null);

        if(!$photo){
            $this->call(400, "bad_request", "Por favor, envie uma foto do tipo JPG, JPEG ou PNG", "error")->back();
            return;
        }

        $imageUploader = new ImageUploader();
        // A foto de perfil é exibida pequena; 600px de largura já é suficiente
        $upload = $imageUploader->upload($photo, ImageUploader::PROFILE_WIDTH);

        if(!$upload){
            $this->call(400, "bad_request", $imageUploader->getErrorMessage(), "error")->back();
            return;
        }

        $user = new User();
        try {
            if (!$user->replacePhoto($this->userAuthId, $upload, $previous)) {
                $imageUploader->remove($upload);
                $this->call(404, "not_found", "Perfil não encontrado.", "error")->back();
                return;
            }
        } catch (\PDOException $error) {
            $imageUploader->remove($upload);
            $this->call(500, "internal_server_error", "Não foi possível salvar a foto.", "error")->back();
            return;
        }
        if ($previous && !$imageUploader->remove($previous)) {
            $this->call(500, "internal_server_error", "Nova foto salva, mas a foto anterior não pôde ser removida.", "error")->back();
            return;
        }

        $this->call(200,"success","Foto atualizada com sucesso","success")->back([
            "id" => $this->userAuthId,
            "photo" => $user->getPhoto()
        ]);
    }

    public function listAll (array $data): void
    {
        if(!$this->authToken (1)){
            $this->authFail();
            return;
        }

        $users = new User();
        $response = array_map(fn($row) => [
            "id" => $row->id,
            "type_id" => $row->type_id,
            "name" => $row->name,
            "email" => $row->email,
            "photo" => $row->photo,
            "active" => $row->active
        ], $users->selectAll());

        $this->call(200,"success","Lista de Usuários","success")->back($response);
    }

    public function listById (array $data): void
    {
        if(!$this->authToken (1)){
            $this->authFail();
            return;
        }

        if(!isset($data["userId"]) || !filter_var($data["userId"], FILTER_VALIDATE_INT)){
            $this->call(400, "bad_request", "ID do usuário é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        $user = new User();
        if(!$user->selectById((int)$data["userId"])){
            $this->call(404, "not_found", "Usuário não encontrado", "error")->back();
            return;
        }

        $response = [
            "id" => $user->getId(),
            "type_id" => $user->getTypeId(),
            "name" => $user->getName(),
            "email" => $user->getEmail(),
            "photo" => $user->getPhoto(),
            "active" => $user->getActive()
        ];

        $this->call(200,"success","Usuário encontrado","success")->back($response);
    }

    public function delete (array $data): void
    {
        if(!$this->authToken (1)){
            $this->authFail();
            return;
        }

        if(!isset($data["userId"]) || !filter_var($data["userId"], FILTER_VALIDATE_INT)){
            $this->call(400, "bad_request", "ID do usuário é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        $user = new User();
        // soft delete
        if ((int)$data['userId'] === $this->userAuthId) {
            $this->call(400, 'bad_request', 'Você não pode inativar seu próprio acesso.', 'error')->back();
            return;
        }
        if(!$user->softDeleteById((int)$data["userId"])){
            $this->call(404, "not_found", "Usuário não encontrado ou já inativo", "error")->back();
            return;
        }

        $this->call(200,"success","Usuário excluído com sucesso","success")->back();
    }

    /**
     * Resposta padrão quando o cadastro falha.
     *
     * E-mail repetido é erro do cliente (**400**), não do servidor: ele consegue
     * corrigir mandando outro endereço. Antes esse caso devolvia 500, o que era
     * incoerente — `update()` e `updateAdmin()` já respondiam 400 para exatamente
     * a mesma condição. Só falha real de banco continua sendo 500.
     */
    private function failedInsert (User $user): void
    {
        if($user->hasEmailConflict()){
            $this->call(400, "bad_request", $user->getErrorMessage(), "error")->back();
            return;
        }

        $this->call(500, "internal_server_error", $user->getErrorMessage(), "error")->back();
    }

    // Valida somente Nome e Email, mas pode ser alterada para validar mais campos
    private function validateNameEmail(array $data): bool
    {
        if(!isset($data["name"],$data["email"]) ||
            !is_string($data['name']) || trim($data['name']) === '' || mb_strlen($data['name']) > 255 ||
            !is_string($data['email']) || mb_strlen($data['email']) > 255 ||
            !filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return true;
    }

    private function validateCredentials(array $data): bool
    {
        return isset($data["email"], $data["password"]) &&
            filter_var($data["email"], FILTER_VALIDATE_EMAIL) &&
            trim((string)$data["password"]) !== "";
    }
}
