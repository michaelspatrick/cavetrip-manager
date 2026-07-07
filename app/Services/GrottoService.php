<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use PDO;

final class GrottoService
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return array<string, mixed>|null */
    public function findForUser(array $user): ?array
    {
        if (($user['role'] ?? '') === 'super_admin') {
            $stmt = $this->db->query('SELECT * FROM grottos ORDER BY id LIMIT 1');
            $grotto = $stmt->fetch();
            return $grotto ?: null;
        }

        $grottoId = (int)($user['grotto_id'] ?? 0);
        if ($grottoId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM grottos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $grottoId]);
        $grotto = $stmt->fetch();
        return $grotto ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function listActive(): array
    {
        return $this->db->query('SELECT * FROM grottos WHERE active = 1 ORDER BY name')->fetchAll();
    }

    public function create(string $name, string $slug): int
    {
        $stmt = $this->db->prepare('INSERT INTO grottos (name, slug, active) VALUES (:name, :slug, 1)');
        $stmt->execute([
            'name' => trim($name),
            'slug' => $this->normalizeSlug($slug),
        ]);
        return (int)$this->db->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $grottoId, array $data): void
    {
        $stmt = $this->db->prepare('UPDATE grottos SET
            name = :name,
            slug = :slug,
            email = :email,
            phone = :phone,
            website_url = :website_url,
            mailing_address = :mailing_address,
            contact_name = :contact_name,
            logo_url = :logo_url,
            logo_file_path = :logo_file_path,
            updated_at = NOW()
            WHERE id = :id');

        $stmt->execute([
            'id' => $grottoId,
            'name' => trim((string)$data['name']),
            'slug' => $this->normalizeSlug((string)$data['slug']),
            'email' => $this->nullableString($data['email'] ?? null),
            'phone' => $this->nullableString($data['phone'] ?? null),
            'website_url' => $this->nullableString($data['website_url'] ?? null),
            'mailing_address' => $this->nullableString($data['mailing_address'] ?? null),
            'contact_name' => $this->nullableString($data['contact_name'] ?? null),
            'logo_url' => $this->nullableString($data['logo_url'] ?? null),
            'logo_file_path' => $this->nullableString($data['logo_file_path'] ?? null),
        ]);
    }

    private function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?: '';
        return trim($slug, '-');
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }
}
