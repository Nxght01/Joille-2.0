<?php
namespace Source\Models;

use Source\Core\Connect;

/** A identidade e o preço vêm do servidor, nunca do formulário do cliente. */
class ServiceRequest
{
    private const SELECT = 'SELECT r.*, s.name AS service_name, u.name AS customer_name,
        u.email AS customer_email, (r.quantity * r.unit_price) AS total
        FROM service_requests r JOIN services s ON s.id = r.services_id
        JOIN users u ON u.id = r.users_id';

    public function all(int $userId, bool $admin): array
    {
        $stmt = Connect::getInstance()->prepare(self::SELECT . ($admin ? '' : ' WHERE r.users_id = ?') . ' ORDER BY r.id DESC');
        $stmt->execute($admin ? [] : [$userId]);
        return $stmt->fetchAll();
    }

    public function find(int $id, int $userId, bool $admin): object
    {
        $stmt = Connect::getInstance()->prepare(self::SELECT . ' WHERE r.id = ?' . ($admin ? '' : ' AND r.users_id = ?'));
        $stmt->execute($admin ? [$id] : [$id, $userId]);
        $row = $stmt->fetch();
        if (!$row) throw new \DomainException('Solicitação não encontrada.', 404);
        return $row;
    }

    public function create(int $userId, int $serviceId, string $description, int $quantity): object
    {
        $db = Connect::getInstance();
        try {
            $db->beginTransaction();
            $service = $db->prepare('SELECT s.value FROM services s JOIN categories c ON c.id = s.categories_id WHERE s.id = ? AND s.active = 1 AND c.active = 1 FOR UPDATE');
            $service->execute([$serviceId]);
            $price = $service->fetchColumn();
            if ($price === false) throw new \DomainException('Serviço indisponível.', 400);
            $stmt = $db->prepare('INSERT INTO service_requests (users_id, services_id, description, quantity, unit_price) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$userId, $serviceId, $description, $quantity, $price]);
            $id = (int)$db->lastInsertId();
            $db->commit();
            return $this->find($id, $userId, false);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public function change(int $id, int $userId, bool $admin, array $data, bool $cancel = false): object
    {
        $db = Connect::getInstance();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare('SELECT * FROM service_requests WHERE id = ?' . ($admin ? '' : ' AND users_id = ?') . ' FOR UPDATE');
            $stmt->execute($admin ? [$id] : [$id, $userId]);
            $current = $stmt->fetch();
            if (!$current) throw new \DomainException('Solicitação não encontrada.', 404);
            if (!$admin && $current->status !== 'pending') throw new \DomainException('Somente solicitações pendentes podem ser alteradas ou canceladas pelo cliente.', 409);
            if ($admin || $cancel) {
                $status = $cancel ? 'cancelled' : ($data['status'] ?? $current->status);
                $allowed = [
                    'pending' => ['pending', 'in_progress', 'cancelled'],
                    'in_progress' => ['in_progress', 'completed', 'cancelled'],
                    'completed' => ['completed'], 'cancelled' => ['cancelled']
                ];
                if (!in_array($status, $allowed[$current->status], true)) throw new \DomainException('Transição de atendimento inválida.', 409);
                $notes = $admin ? trim($data['admin_notes'] ?? $current->admin_notes ?? '') : $current->admin_notes;
                if (mb_strlen($notes ?? '') > 2000) throw new \DomainException('Use até 2000 caracteres na observação.', 400);
                $estimate = $current->preliminary_value;
                if ($admin && array_key_exists('preliminary_value', $data)) {
                    $estimate = $this->validateEstimate($data['preliminary_value']);
                }
                $stmt = $db->prepare('UPDATE service_requests SET status = ?, admin_notes = ?, preliminary_value = ? WHERE id = ?');
                $stmt->execute([$status, $notes, $estimate, $id]);
            } else {
                $stmt = $db->prepare('UPDATE service_requests SET description = ?, quantity = ? WHERE id = ?');
                $stmt->execute([$data['description'], $data['quantity'], $id]);
            }
            $db->commit();
            return $this->find($id, $userId, $admin);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    private function validateEstimate(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new \DomainException('Informe um valor preliminar válido.', 400);
        }
        $value = str_replace(',', '.', trim((string)$value));
        if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', $value)) {
            throw new \DomainException('Use um valor entre 0 e 9.999.999.999,99, com até duas casas decimais.', 400);
        }
        return $value;
    }
}
