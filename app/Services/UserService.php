<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use PDO;

final class UserService
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listForGrotto(?int $grottoId): array
    {
        if ($grottoId === null) {
            return $this->db->query('SELECT users.*, grottos.name AS grotto_name FROM users LEFT JOIN grottos ON grottos.id = users.grotto_id ORDER BY users.name')->fetchAll();
        }

        $stmt = $this->db->prepare('SELECT users.*, grottos.name AS grotto_name FROM users LEFT JOIN grottos ON grottos.id = users.grotto_id WHERE users.grotto_id = :grotto_id ORDER BY users.name');
        $stmt->execute(['grotto_id' => $grottoId]);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id, ?int $grottoId): ?array
    {
        if ($grottoId === null) {
            $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
        } else {
            $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id AND grotto_id = :grotto_id LIMIT 1');
            $stmt->execute(['id' => $id, 'grotto_id' => $grottoId]);
        }
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function createUser(?int $grottoId, string $role, string $name, string $email, ?string $phone, ?string $password): int
    {
        if ($role !== 'super_admin' && ($grottoId === null || $grottoId <= 0)) {
            throw new \InvalidArgumentException('A grotto is required for Admin, Member, and Guest accounts.');
        }

        $stmt = $this->db->prepare('INSERT INTO users (grotto_id, role, name, email, phone, password_hash, active, created_at) VALUES (:grotto_id, :role, :name, :email, :phone, :password_hash, 1, NOW())');
        $stmt->execute([
            'grotto_id' => $grottoId,
            'role' => $role,
            'name' => $this->required($name, 'Name is required.'),
            'email' => $this->email($email),
            'phone' => $this->nullable($phone),
            'password_hash' => $password !== null && $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateUser(int $id, ?int $scopeGrottoId, array $data, bool $mayChangeGrotto = false): void
    {
        $role = (string)($data['role'] ?? 'guest');
        if (!in_array($role, ['super_admin', 'admin', 'member', 'guest'], true)) {
            throw new \InvalidArgumentException('Invalid role.');
        }

        $assignedGrottoId = null;
        if ($mayChangeGrotto) {
            $rawGrotto = trim((string)($data['grotto_id'] ?? ''));
            $assignedGrottoId = $rawGrotto === '' ? null : (int)$rawGrotto;
            if ($role !== 'super_admin' && ($assignedGrottoId === null || $assignedGrottoId <= 0)) {
                throw new \InvalidArgumentException('A grotto is required for Admin, Member, and Guest accounts.');
            }
        }

        $params = [
            'id' => $id,
            'name' => $this->required((string)($data['name'] ?? ''), 'Name is required.'),
            'email' => $this->email((string)($data['email'] ?? '')),
            'phone' => $this->nullable($data['phone'] ?? null),
            'role' => $role,
            'active' => isset($data['active']) ? 1 : 0,
        ];

        $where = 'id = :id';
        if ($scopeGrottoId !== null) {
            $where .= ' AND grotto_id = :scope_grotto_id';
            $params['scope_grotto_id'] = $scopeGrottoId;
        }

        $set = 'name = :name, email = :email, phone = :phone, role = :role, active = :active, updated_at = NOW()';
        if ($mayChangeGrotto) {
            $set = 'grotto_id = :assigned_grotto_id, ' . $set;
            $params['assigned_grotto_id'] = $assignedGrottoId;
        }

        $stmt = $this->db->prepare("UPDATE users SET {$set} WHERE {$where}");
        $stmt->execute($params);

        if ($stmt->rowCount() === 0 && $this->find($id, $scopeGrottoId) === null) {
            throw new \RuntimeException('User not found.');
        }

        $newPassword = (string)($data['password'] ?? '');
        if ($newPassword !== '') {
            if (strlen($newPassword) < 12) {
                throw new \InvalidArgumentException('New passwords must be at least 12 characters.');
            }
            $passwordParams = ['id' => $id, 'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)];
            $passwordWhere = 'id = :id';
            if ($scopeGrottoId !== null) {
                $passwordWhere .= ' AND grotto_id = :scope_grotto_id';
                $passwordParams['scope_grotto_id'] = $scopeGrottoId;
            }
            $passwordStmt = $this->db->prepare("UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE {$passwordWhere}");
            $passwordStmt->execute($passwordParams);
        }
    }

    private function required(string $value, string $message): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException($message);
        }
        return $value;
    }

    private function email(string $value): string
    {
        $value = strtolower(trim($value));
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A valid email is required.');
        }
        return $value;
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }
}
