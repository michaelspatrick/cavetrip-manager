<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use PDO;

final class LandownerService
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listForGrotto(int $grottoId, bool $includeInactive = false): array
    {
        $sql = 'SELECT * FROM landowners WHERE grotto_id = :grotto_id';
        if (!$includeInactive) {
            $sql .= ' AND active = 1';
        }
        $sql .= ' ORDER BY name';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['grotto_id' => $grottoId]);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findForGrotto(int $id, int $grottoId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM landowners WHERE id = :id AND grotto_id = :grotto_id LIMIT 1');
        $stmt->execute(['id' => $id, 'grotto_id' => $grottoId]);
        $landowner = $stmt->fetch();
        return $landowner ?: null;
    }

    /** @param array<string, mixed> $data */
    public function create(int $grottoId, array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO landowners
            (grotto_id, name, email, phone, mailing_address, preferred_contact_method, notes, active, created_at)
            VALUES (:grotto_id, :name, :email, :phone, :mailing_address, :preferred_contact_method, :notes, :active, NOW())');

        $stmt->execute([
            'grotto_id' => $grottoId,
            'name' => $this->requiredString($data['name'] ?? '', 'Landowner name is required.'),
            'email' => $this->nullableString($data['email'] ?? null),
            'phone' => $this->nullableString($data['phone'] ?? null),
            'mailing_address' => $this->nullableString($data['mailing_address'] ?? null),
            'preferred_contact_method' => $this->nullableString($data['preferred_contact_method'] ?? null),
            'notes' => $this->nullableString($data['notes'] ?? null),
            'active' => isset($data['active']) ? 1 : 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, int $grottoId, array $data): void
    {
        $stmt = $this->db->prepare('UPDATE landowners SET
            name = :name,
            email = :email,
            phone = :phone,
            mailing_address = :mailing_address,
            preferred_contact_method = :preferred_contact_method,
            notes = :notes,
            active = :active,
            updated_at = NOW()
            WHERE id = :id AND grotto_id = :grotto_id');

        $stmt->execute([
            'id' => $id,
            'grotto_id' => $grottoId,
            'name' => $this->requiredString($data['name'] ?? '', 'Landowner name is required.'),
            'email' => $this->nullableString($data['email'] ?? null),
            'phone' => $this->nullableString($data['phone'] ?? null),
            'mailing_address' => $this->nullableString($data['mailing_address'] ?? null),
            'preferred_contact_method' => $this->nullableString($data['preferred_contact_method'] ?? null),
            'notes' => $this->nullableString($data['notes'] ?? null),
            'active' => isset($data['active']) ? 1 : 0,
        ]);
    }

    private function requiredString(mixed $value, string $message): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            throw new \InvalidArgumentException($message);
        }
        return $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }
}
