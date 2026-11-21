<?php
namespace Source\Controller;

use Source\Models\ServiceRequest;

class Requests extends Api
{
    public function listAll(array $data): void
    {
        $this->run(fn() => (new ServiceRequest())->all($this->userAuthId, $this->userAuthType === 1));
    }

    public function listById(array $data): void
    {
        $this->run(fn() => (new ServiceRequest())->find($this->id($data['requestId'] ?? null), $this->userAuthId, $this->userAuthType === 1));
    }

    public function insert(array $data): void
    {
        $this->run(function () use ($data) {
            $this->validate($data);
            return (new ServiceRequest())->create($this->userAuthId, $this->id($data['services_id'] ?? null), trim($data['description']), (int)$data['quantity']);
        }, 201, 'Solicitação criada com sucesso.', [2]);
    }

    public function update(array $data): void
    {
        $this->run(function () use ($data) {
            if ($this->userAuthType === 2) $this->validate($data);
            return (new ServiceRequest())->change($this->id($data['requestId'] ?? null), $this->userAuthId, $this->userAuthType === 1, $data);
        }, 200, 'Solicitação atualizada.');
    }

    public function delete(array $data): void
    {
        $this->run(fn() => (new ServiceRequest())->change($this->id($data['requestId'] ?? null), $this->userAuthId, $this->userAuthType === 1, [], true), 200, 'Solicitação cancelada.');
    }

    private function run(callable $operation, int $code = 200, string $message = 'Solicitações consultadas.', array $roles = [1, 2]): void
    {
        if (!$this->authToken($roles)) { $this->authFail(); return; }
        try {
            $result = $operation();
            $this->call($code, $code === 201 ? 'created' : 'success', $message, 'success')->back($result);
        } catch (\DomainException $e) {
            $statuses = [400 => 'bad_request', 404 => 'not_found', 409 => 'conflict'];
            $this->call($e->getCode(), $statuses[$e->getCode()], $e->getMessage(), 'error')->back();
        }
    }

    private function id(mixed $value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) throw new \DomainException('Informe um identificador inteiro positivo.', 400);
        return $id;
    }

    private function validate(array $data): void
    {
        if (!isset($data['description']) || !is_string($data['description']) || mb_strlen(trim($data['description'])) < 10 || mb_strlen($data['description']) > 2000) {
            throw new \DomainException('Descreva o atendimento em 10 a 2000 caracteres.', 400);
        }
        if (!filter_var($data['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]])) {
            throw new \DomainException('A quantidade deve estar entre 1 e 100.', 400);
        }
    }
}
