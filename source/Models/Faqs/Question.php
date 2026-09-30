<?php

namespace Source\Models\Faqs;

use PDOException;
use Source\Core\Connect;
use Source\Core\Model;

class Question extends Model
{
    private ?int $id;
    private ?int $typesId;
    private ?string $question;
    private ?string $answer;
    private ?int $active;

    public function __construct(
        ?int $id = null,
        ?int $typesId = null,
        ?string $question = null,
        ?string $answer = null,
        ?int $active = 1
    ) {
        $this->id = $id;
        $this->typesId = $typesId;
        $this->question = $question;
        $this->answer = $answer;
        $this->active = $active;

        $this->table = 'questions';
        $this->primaryKey = 'id';
        $this->fillable = ['typesId', 'question', 'answer', 'active'];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTypesId(): ?int
    {
        return $this->typesId;
    }

    public function setTypesId(int $typesId): void
    {
        $this->typesId = $typesId;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): void
    {
        $this->question = $question;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): void
    {
        $this->answer = $answer;
    }

    public function getActive(): ?int
    {
        return $this->active;
    }

    public function setActive(int $active): void
    {
        $this->active = $active;
    }

    /**
     * Perguntas publicadas e não publicadas por tipo — alimenta o gráfico do
     * dashboard e mostra onde há conteúdo esperando revisão.
     *
     * @return array<int, object{type_description: string, published: int, unpublished: int}>
     */
    public function statsByType(): array
    {
        try {
            $query = "SELECT
                        types.description AS type_description,
                        SUM(CASE WHEN questions.active = 1 THEN 1 ELSE 0 END) AS published,
                        SUM(CASE WHEN questions.active = 0 THEN 1 ELSE 0 END) AS unpublished
                      FROM types
                      LEFT JOIN questions ON questions.types_id = types.id
                      WHERE types.active = 1
                      GROUP BY types.id, types.description
                      ORDER BY types.id ASC";

            $stmt = Connect::getInstance()->query($query);
            return $stmt->fetchAll();
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
            return [];
        }
    }

    /** Perguntas com o nome do tipo, para o relatório em PDF. */
    public function selectAllForReport(): array
    {
        try {
            $query = "SELECT
                        questions.id,
                        questions.question,
                        questions.answer,
                        questions.active,
                        types.description AS type_description
                      FROM questions
                      INNER JOIN types ON types.id = questions.types_id
                      ORDER BY types.id ASC, questions.id ASC";

            $stmt = Connect::getInstance()->query($query);
            return $stmt->fetchAll();
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
            return [];
        }
    }
}
