<?php

namespace Source\Models;

use PDO;
use PDOException;
use Source\Core\Connect;
use Source\Core\Model;

/**
 * Token de uso único do fluxo "esqueci minha senha" (`password_resets`).
 *
 * Um token vale por CONF_PASSWORD_RESET_EXPIRE minutos e só pode ser consumido
 * uma vez — depois disso `used_at` fica preenchido e ele deixa de ser aceito.
 */
class PasswordReset extends Model
{
    private ?int $id;
    private ?int $usersId;
    private ?string $token;
    private ?string $expiresAt;
    private ?string $usedAt;

    public function __construct(
        ?int $id = null,
        ?int $usersId = null,
        ?string $token = null,
        ?string $expiresAt = null,
        ?string $usedAt = null
    ) {
        $this->id = $id;
        $this->usersId = $usersId;
        $this->token = $token;
        $this->expiresAt = $expiresAt;
        $this->usedAt = $usedAt;

        $this->table = 'password_resets';
        $this->primaryKey = 'id';
        $this->fillable = ['usersId', 'token', 'expiresAt', 'usedAt'];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUsersId(): ?int
    {
        return $this->usersId;
    }

    public function setUsersId(?int $usersId): void
    {
        $this->usersId = $usersId;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): void
    {
        $this->token = $token;
    }

    public function getExpiresAt(): ?string
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?string $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function getUsedAt(): ?string
    {
        return $this->usedAt;
    }

    public function setUsedAt(?string $usedAt): void
    {
        $this->usedAt = $usedAt;
    }

    /**
     * Cria um token novo para o usuário e invalida os anteriores, para que um
     * pedido repetido não deixe vários links válidos circulando por e-mail.
     *
     * @return string|null o token gerado, ou null em caso de falha
     */
    public function issueFor(int $userId): ?string
    {
        try {
            $this->invalidateFor($userId);

            $token = bin2hex(random_bytes(32)); // 64 caracteres, cabe em char(64)
            $expires = (new \DateTimeImmutable())
                ->modify("+" . CONF_PASSWORD_RESET_EXPIRE . " minutes")
                ->format("Y-m-d H:i:s");

            $stmt = Connect::getInstance()->prepare(
                "INSERT INTO {$this->table} (users_id, token, expires_at)
                 VALUES (:user, :token, :expires)"
            );
            $stmt->bindValue(":user", $userId, PDO::PARAM_INT);
            $stmt->bindValue(":token", $token);
            $stmt->bindValue(":expires", $expires);
            $stmt->execute();

            $this->id = (int)Connect::getInstance()->lastInsertId();
            $this->usersId = $userId;
            $this->token = $token;
            $this->expiresAt = $expires;

            return $token;
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
            return null;
        } catch (\Exception $exception) {
            $this->errorMessage = "Não foi possível gerar o token: " . $exception->getMessage();
            return null;
        }
    }

    /**
     * Localiza um token ainda válido (não usado e dentro do prazo) e devolve o
     * registro junto com o nome e o e-mail do usuário.
     */
    public function findValid(string $token): object|false
    {
        try {
            $stmt = Connect::getInstance()->prepare(
                "SELECT r.id, r.users_id, r.expires_at, u.name, u.email
                 FROM {$this->table} r
                 INNER JOIN users u ON u.id = r.users_id
                 WHERE r.token = :token
                   AND r.used_at IS NULL
                   AND r.expires_at > NOW()
                   AND u.active = 1
                 LIMIT 1"
            );
            $stmt->bindValue(":token", $token);
            $stmt->execute();

            $result = $stmt->fetch();

            if (!$result) {
                $this->errorMessage = "Link de recuperação inválido, expirado ou já utilizado.";
                return false;
            }

            return $result;
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
            return false;
        }
    }

    /** Marca o token como consumido. */
    public function consume(int $id): bool
    {
        try {
            $stmt = Connect::getInstance()->prepare(
                "UPDATE {$this->table} SET used_at = NOW() WHERE id = :id AND used_at IS NULL"
            );
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() < 1) {
                $this->errorMessage = "Token já utilizado.";
                return false;
            }

            return true;
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
            return false;
        }
    }

    /** Invalida todos os tokens pendentes de um usuário. */
    public function invalidateFor(int $userId): bool
    {
        try {
            $stmt = Connect::getInstance()->prepare(
                "UPDATE {$this->table} SET used_at = NOW()
                 WHERE users_id = :user AND used_at IS NULL"
            );
            $stmt->bindValue(":user", $userId, PDO::PARAM_INT);
            $stmt->execute();

            return true;
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
            return false;
        }
    }
}
