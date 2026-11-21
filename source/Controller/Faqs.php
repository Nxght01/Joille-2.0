<?php

namespace Source\Controller;

use Source\Controller\Api;
use Source\Models\Faqs\Question;

class Faqs extends Api
{
    public function listAll(array $data): void
    {
        $question = new Question();
        $this->call(200, "success", "Lista de Perguntas Frequentes", "success")
            ->back($question->selectAll(["active = 1"]));
    }

    public function listAllAdmin(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        $question = new Question();
        $this->call(200, "success", "Lista administrativa de Perguntas Frequentes", "success")
            ->back($question->selectAll());
    }

    public function listById(array $data): void
    {
        if (!isset($data["faqId"]) || empty($data["faqId"]) || !filter_var($data["faqId"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request", "ID da FAQ é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        $question = new Question();
        if (!$question->selectById((int)$data["faqId"]) || (!$question->getActive() && !$this->authToken(1))) {
            $this->call(404, "not_found", "FAQ não encontrada", "error")->back();
            return;
        }

        $this->call(200, "success", "FAQ encontrada", "success")->back($this->present($question));
    }

    public function listByType(array $data): void
    {
        if (!isset($data["typeId"]) || empty($data["typeId"]) || !filter_var($data["typeId"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request", "ID do tipo é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        $question = new Question();
        $typeId = (int)$data["typeId"];
        $this->call(200, "success", "Lista de FAQs por tipo", "success")
            ->back($question->selectAll(["active = 1", "types_id = {$typeId}"]));
    }

    public function insert(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        if (!isset($data["types_id"]) || empty($data["types_id"]) || !filter_var($data["types_id"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request", "O campo types_id é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        if (!isset($data["question"]) || empty(trim($data["question"]))) {
            $this->call(400, "bad_request", "O campo question é obrigatório", "error")->back();
            return;
        }

        if (!isset($data["answer"]) || empty(trim($data["answer"]))) {
            $this->call(400, "bad_request", "O campo answer é obrigatório", "error")->back();
            return;
        }

        $question = new Question(null, (int)$data["types_id"], $data["question"], $data["answer"], 1);
        if (!$question->insert()) {
            $this->call(500, "internal_server_error", $question->getErrorMessage(), "error")->back();
            return;
        }

        $this->call(201, "created", "FAQ criada com sucesso", "success")->back($this->present($question));
    }

    public function update(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        if (!isset($data["faqId"]) || !filter_var($data["faqId"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request", "O campo faqId é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        $question = new Question();
        if (!$question->selectById((int)$data["faqId"])) {
            $this->call(404, "not_found", "FAQ não encontrada", "error")->back();
            return;
        }

        if (isset($data["types_id"]) && !empty($data["types_id"])) {
            if (!filter_var($data["types_id"], FILTER_VALIDATE_INT)) {
                $this->call(400, "bad_request", "O campo types_id deve ser um número inteiro", "error")->back();
                return;
            }
            $question->setTypesId((int)$data["types_id"]);
        }

        if (isset($data["question"]) && !empty(trim($data["question"]))) {
            $question->setQuestion($data["question"]);
        }

        if (isset($data["answer"]) && !empty(trim($data["answer"]))) {
            $question->setAnswer($data["answer"]);
        }

        if (array_key_exists("active", $data)) {
            $active = filter_var($data["active"], FILTER_VALIDATE_INT);
            if ($active === false || !in_array($active, [0, 1], true)) {
                $this->call(400, "bad_request", "O campo active deve ser 0 ou 1", "error")->back();
                return;
            }
            $question->setActive($active);
        }

        if (!$question->updateById((int)$data["faqId"])) {
            $this->call(500, "internal_server_error", $question->getErrorMessage(), "error")->back();
            return;
        }

        $this->call(200, "success", "FAQ atualizada com sucesso", "success")->back($this->present($question));
    }

    public function delete(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        if (!isset($data["faqId"]) || !filter_var($data["faqId"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request", "ID da FAQ é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        $question = new Question();
        if (!$question->softDeleteById((int)$data["faqId"])) {
            $this->call(404, "not_found", "FAQ não encontrada ou já inativa", "error")->back();
            return;
        }

        $this->call(200, "success", "FAQ removida com sucesso", "success")->back();
    }

    private function present(Question $question): array
    {
        return [
            "id" => $question->getId(),
            "types_id" => $question->getTypesId(),
            "question" => $question->getQuestion(),
            "answer" => $question->getAnswer(),
            "active" => $question->getActive()
        ];
    }
}
