<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use PDO;

final class WaiverTemplateService
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listForGrotto(int $grottoId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM waiver_templates WHERE grotto_id = :grotto_id ORDER BY active DESC, name ASC');
        $stmt->execute(['grotto_id' => $grottoId]);
        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listActiveForGrotto(int $grottoId): array
    {
        $stmt = $this->db->prepare('SELECT id, name FROM waiver_templates WHERE grotto_id = :grotto_id AND active = 1 ORDER BY name');
        $stmt->execute(['grotto_id' => $grottoId]);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findForGrotto(int $id, int $grottoId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM waiver_templates WHERE id = :id AND grotto_id = :grotto_id LIMIT 1');
        $stmt->execute(['id' => $id, 'grotto_id' => $grottoId]);
        $template = $stmt->fetch();
        return $template ?: null;
    }

    /** @param array<string, mixed> $data */
    public function create(int $grottoId, array $data): int
    {
        $params = $this->bindData($grottoId, $data);
        $stmt = $this->db->prepare('INSERT INTO waiver_templates
            (grotto_id, name, slug, description, html_body, active, created_at)
            VALUES
            (:grotto_id, :name, :slug, :description, :html_body, :active, NOW())');
        $stmt->execute($params);
        return (int)$this->db->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, int $grottoId, array $data): void
    {
        if ($this->findForGrotto($id, $grottoId) === null) {
            throw new \InvalidArgumentException('Waiver template not found.');
        }

        $params = $this->bindData($grottoId, $data);
        $params['id'] = $id;

        $stmt = $this->db->prepare('UPDATE waiver_templates SET
            name = :name,
            slug = :slug,
            description = :description,
            html_body = :html_body,
            active = :active,
            updated_at = NOW()
            WHERE id = :id AND grotto_id = :grotto_id');
        $stmt->execute($params);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function bindData(int $grottoId, array $data): array
    {
        $name = $this->requiredString($data['name'] ?? '', 'Template name is required.');
        $slug = trim((string)($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = $this->slugify($name);
        }
        $slug = $this->slugify($slug);

        $htmlBody = trim((string)($data['html_body'] ?? ''));
        if ($htmlBody === '') {
            throw new \InvalidArgumentException('Waiver HTML body is required.');
        }

        return [
            'grotto_id' => $grottoId,
            'name' => $name,
            'slug' => $slug,
            'description' => $this->nullableString($data['description'] ?? null),
            'html_body' => $htmlBody,
            'active' => isset($data['active']) ? 1 : 0,
        ];
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

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: '';
        $value = trim($value, '-');
        if ($value === '') {
            throw new \InvalidArgumentException('Template slug is required.');
        }
        return $value;
    }
}
