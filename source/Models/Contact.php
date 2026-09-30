<?php

namespace Source\Models;

use PDO;
use PDOException;
use Source\Core\Connect;
use Source\Core\Model;

/**
 * Mensagem enviada pelo formulário de contato da área pública (`contacts`).
 *
 * `users_id` é opcional: o formulário é aberto a visitantes, e só é preenchido
 * quando a mensagem parte de alguém já cadastrado.
 *
 * Os nomes de coluna `contactName` e `contactEmaill` vêm do schema original do
 * banco e são mantidos para não quebrar dados existentes.
 */
class Contact extends Model
{
    private ?int $id;
    private ?int $usersId;
    private ?string $contactEmaill;
    private ?string $contactName;
    private ?string $subject;
    private ?string $message;
    private ?int $answered;

    public function __construct(
        ?int $id = null,
        ?string $contactName = null,
        ?string $contactEmaill = null,
        ?string $subject = null,
        ?string $message = null,
        ?int $usersId = null,
        ?int $answered = 0
    ) {
        $this->id = $id;
        $this->contactName = $contactName;
        $this->contactEmaill = $contactEmaill;
        $this->subject = $subject;
        $this->message = $message;
        $this->usersId = $usersId;
        $this->answered = $answered;

        $this->table = 'contacts';
        $this->primaryKey = 'id';
        $this->fillable = ['usersId', 'contactName', 'contactEmaill', 'subject', 'message', 'answered'];
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

    public function getContactEmaill(): ?string
    {
        return $this->contactEmaill;
    }

    public function setContactEmaill(?string $contactEmaill): void
    {
        $this->contactEmaill = $contactEmaill;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(?string $contactName): void
    {
        $this->contactName = $contactName;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): void
    {
        $this->subject = $subject;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    public function getAnswered(): ?int
    {
        return $this->answered;
    }

    public function setAnswered(?int $answered): void
    {
        $this->answered = $answered;
    }

    /**
     * Duas colunas desta tabela já nascem em camelCase no schema original
     * (`contactName` e `contactEmaill`), então a conversão automática
     * camelCase -> snake_case do Model precisa ser desligada para elas.
     */
    private const LITERAL_COLUMNS = ['contactName', 'contactEmaill'];

    protected function camelToSnake(string $field): string
    {
        if (in_array($field, self::LITERAL_COLUMNS, true)) {
            return $field;
        }

        return parent::camelToSnake($field);
    }

    protected function snakeToCamel(string $field): string
    {
        foreach (self::LITERAL_COLUMNS as $column) {
            if (strcasecmp($field, $column) === 0) {
                return $column;
            }
        }

        return parent::snakeToCamel($field);
    }

    /** Mensagens mais recentes primeiro, para a listagem do painel. */
    public function selectLatest(int $limit = 100): array
    {
        try {
            $limit = max(1, min($limit, 500));

            $stmt = Connect::getInstance()->query(
                "SELECT id, users_id, contactName, contactEmaill, subject, message, answered, created_at
                 FROM {$this->table}
                 ORDER BY id DESC
                 LIMIT {$limit}"
            );

            return $stmt->fetchAll();
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
            return [];
        }
    }

    /** Marca a mensagem como respondida/lida. */
    public function markAnswered(int $id, int $answered = 1): bool
    {
        try {
            $stmt = Connect::getInstance()->prepare(
                "UPDATE {$this->table} SET answered = :answered WHERE {$this->primaryKey} = :id"
            );
            $stmt->bindValue(":answered", $answered, PDO::PARAM_INT);
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() < 1) {
                $this->errorMessage = "Mensagem não encontrada ou sem alterações.";
                return false;
            }

            return true;
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
            return false;
        }
    }
}
