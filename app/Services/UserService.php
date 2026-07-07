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
            return $this->db->query('SELECT users.*, grottos.name AS grotto_name FROM users LEFT JOIN grottos ON grottos.id = users.grotto_id ORDER BY users.created_at DESC')->fetchAll();
        }

        $stmt = $this->db->prepare('SELECT * FROM users WHERE grotto_id = :grotto_id ORDER BY name');
        $stmt->execute(['grotto_id' => $grottoId]);
        return $stmt->fetchAll();
    }

    public function createUser(?int $grottoId, string $role, string $name, string $email, ?string $phone, ?string $password): int
    {
        $stmt = $this->db->prepare('INSERT INTO users (grotto_id, role, name, email, phone, password_hash) VALUES (:grotto_id, :role, :name, :email, :phone, :password_hash)');
        $stmt->execute([
            'grotto_id' => $grottoId,
            'role' => $role,
            'name' => trim($name),
            'email' => strtolower(trim($email)),
            'phone' => $phone !== null ? trim($phone) : null,
            'password_hash' => $password !== null && $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null,
        ]);
        return (int)$this->db->lastInsertId();
    }
}
