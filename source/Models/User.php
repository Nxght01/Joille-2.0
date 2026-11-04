<?php

namespace Source\Models;

use PDO;
use Source\Core\Model;
use Source\Core\Connect;
use Source\Core\JWTToken;

class User extends Model
{
    private ?int $id;
    private ?int $typeId;
    private ?int $statusId;
    private ?int $servicesId;
    private ?string $name;
    private ?string $email;
    private ?string $password;
    private ?string $photo;
    private ?int $active;

    private ?string $token = null;

    /** Diferencia "e-mail repetido" de uma falha real de banco no insert(). */
    private bool $emailConflict = false;

    public function __construct(
        ?int $id = null,
        ?int $typeId = null,
        ?string $name = null,
        ?string $email = null,
        ?string $password = null,
        ?string $photo = null,
        ?int $statusId = null,
        ?int $servicesId = null,
        ?int $active = null
    ) {
        $this->id = $id;
        $this->typeId = $typeId;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->photo = $photo;
        $this->statusId = $statusId;
        $this->servicesId = $servicesId;
        $this->active = $active;

        $this->table = 'users'; // nome da tabela do banco
        $this->primaryKey = 'id'; // nome da chave primária da tabela
        $this->fillable = ['typeId', 'statusId', 'servicesId', 'name', 'email', 'password', 'photo', 'active']; // camelCase
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getTypeId(): ?int
    {
        return $this->typeId;
    }

    public function setTypeId(?int $typeId): void
    {
        $this->typeId = $typeId;
    }

    public function getStatusId(): ?int
    {
        return $this->statusId;
    }

    public function setStatusId(?int $statusId): void
    {
        $this->statusId = $statusId;
    }

    public function getServicesId(): ?int
    {
        return $this->servicesId;
    }

    public function setServicesId(?int $servicesId): void
    {
        $this->servicesId = $servicesId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): void
    {
        $this->photo = $photo;
    }

    public function getActive(): ?int
    {
        return $this->active;
    }

    public function setActive(?int $active): void
    {
        $this->active = $active;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * O e-mail é conferido aqui antes do INSERT, mas quem garante a unicidade é o
     * índice `uq_users_email` (migration 003): entre a checagem e a gravação cabe
     * outra requisição com o mesmo e-mail. Quando isso acontece, o banco recusa e
     * a violação é traduzida na mesma mensagem da checagem — para o cliente da API
     * o resultado é idêntico, com ou sem concorrência.
     */
    public function insert (): bool
    {
        if($this->emailInUse((string)$this->email)){
            $this->errorMessage = "E-mail já cadastrado";
            $this->emailConflict = true;
            return false;
        }

        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        if(!parent::insert()){
            if($this->isDuplicateEntry()){
                $this->errorMessage = "E-mail já cadastrado";
                $this->emailConflict = true;
                return false;
            }

            // A mensagem do banco é preservada: trocá-la por um texto genérico
            // escondia a causa real de qualquer falha de gravação.
            return false;
        }

        return true;
    }

    /** true quando a última falha foi e-mail repetido, e não erro de banco. */
    public function hasEmailConflict (): bool
    {
        return $this->emailConflict;
    }

    public function login (string $email, string $password, int $typeId = 2): bool
    {
        $query = "SELECT * FROM {$this->table} WHERE email = :email AND type_id = :typeId AND active = 1";
        $stmt = Connect::getInstance()->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":typeId", $typeId);
        $stmt->execute();
        if($stmt->rowCount() == 0){
            $this->errorMessage = "Email não cadastrado";
            return false;
        }
        $user = $stmt->fetch();
        if(!password_verify($password, $user->password)){
            $this->errorMessage = "Senha incorreta";
            return false;
        }
        $this->id = $user->id;
        $this->typeId = $user->type_id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->photo = $user->photo;
        $this->active = (int)$user->active;
        $jwt = new JWTToken();
        // definir quais informações irão para o payload do token
        $this->token = $jwt->encode([
            "id" => $user->id,
            "name" => $user->name,
            "email" => $user->email,
            "type_id" => $user->type_id
        ]);
        return true;
    }

    public function permissionVerify (string $email, $typeId): bool
    {
        $query = "SELECT * FROM {$this->table} WHERE email = :email AND type_id = :typeId AND active = 1";
        $stmt = Connect::getInstance()->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":typeId", $typeId);
        $stmt->execute();
        if($stmt->rowCount() == 0) {
            return false;
        }
        return true;
    }

    public function updatePassword (int $id, string $password, string $newPassword): bool
    {
        $query = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = Connect::getInstance()->prepare($query);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        $user = $stmt->fetch();
        if(!$user){
            $this->errorMessage = "Usuário não encontrado";
            return false;
        }

        if(!password_verify($password, $user->password)){
            $this->errorMessage = "Senha incorreta";
            return false;
        }

        $this->password = password_hash($newPassword, PASSWORD_DEFAULT);

        $query = "UPDATE {$this->table} SET password = :password WHERE {$this->primaryKey} = :id";
        $stmt = Connect::getInstance()->prepare($query);
        $stmt->bindValue(":password", $this->password);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        return true;
    }

    // A alteração é transacional; o controller só recebe a foto antiga após o commit.
    public function replacePhoto(int $id, string $photo, ?string &$previous = null): bool
    {
        $db = Connect::getInstance();
        try {
            $db->beginTransaction();
            $read = $db->prepare("SELECT photo FROM users WHERE id = ? AND active = 1 FOR UPDATE");
            $read->execute([$id]);
            $row = $read->fetch();
            if (!$row) {
                $db->rollBack();
                return false;
            }
            $save = $db->prepare("UPDATE users SET photo = ? WHERE id = ?");
            $save->execute([$photo, $id]);
            $db->commit();
            $this->photo = $photo;
            $previous = $row->photo;
            return true;
        } catch (\PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /**
     * Quantos usuários existem por perfil de acesso, separando ativos e inativos —
     * alimenta o gráfico do dashboard administrativo.
     *
     * @return array<int, object{type_name: string, active: int, inactive: int}>
     */
    public function statsByType (): array
    {
        $query = "SELECT
                    users_types.name AS type_name,
                    SUM(CASE WHEN users.active = 1 THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN users.active = 0 THEN 1 ELSE 0 END) AS inactive
                  FROM users_types
                  LEFT JOIN users ON users.type_id = users_types.id
                  GROUP BY users_types.id, users_types.name
                  ORDER BY users_types.id ASC";

        $stmt = Connect::getInstance()->query($query);

        return $stmt->fetchAll();
    }

    /** Usuários com o nome do perfil, para o relatório em PDF. */
    public function selectAllForReport (): array
    {
        $query = "SELECT
                    users.id,
                    users.name,
                    users.email,
                    users.active,
                    users_types.name AS type_name
                  FROM users
                  INNER JOIN users_types ON users_types.id = users.type_id
                  ORDER BY users_types.id ASC, users.name ASC";

        $stmt = Connect::getInstance()->query($query);

        return $stmt->fetchAll();
    }

    /**
     * Localiza um usuário ativo pelo e-mail. Usado pelo fluxo de recuperação de
     * senha, que parte só do e-mail informado no formulário público.
     */
    public function selectActiveByEmail (string $email): bool
    {
        $query = "SELECT * FROM {$this->table} WHERE email = :email AND active = 1 LIMIT 1";
        $stmt = Connect::getInstance()->prepare($query);
        $stmt->bindValue(":email", $email);
        $stmt->execute();

        $user = $stmt->fetch();
        if(!$user){
            $this->errorMessage = "E-mail não cadastrado";
            return false;
        }

        $this->id = (int)$user->id;
        $this->typeId = (int)$user->type_id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->photo = $user->photo;
        $this->active = (int)$user->active;

        return true;
    }

    /**
     * Grava uma senha nova sem exigir a anterior — é o que o link de recuperação
     * enviado por e-mail autoriza, já que quem chega aqui não sabe a senha antiga.
     * A posse do token é validada antes, em Source\Models\PasswordReset.
     */
    public function resetPassword (int $id, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $query = "UPDATE {$this->table} SET password = :password WHERE {$this->primaryKey} = :id";
        $stmt = Connect::getInstance()->prepare($query);
        $stmt->bindValue(":password", $hash);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        if($stmt->rowCount() < 1){
            $this->errorMessage = "Não foi possível atualizar a senha.";
            return false;
        }

        $this->password = $hash;

        return true;
    }

    // O e-mail é único, portanto precisa ser checado contra os demais registros
    public function emailInUse (string $email, ?int $ignoreId = null): bool
    {
        $query = "SELECT id FROM {$this->table} WHERE email = :email";
        if($ignoreId !== null){
            $query .= " AND {$this->primaryKey} != :id";
        }

        $stmt = Connect::getInstance()->prepare($query);
        $stmt->bindValue(":email", $email);
        if($ignoreId !== null){
            $stmt->bindValue(":id", $ignoreId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
