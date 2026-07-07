<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use PDO;

final class CaveService
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listForGrotto(int $grottoId, bool $includeInactive = false): array
    {
        $sql = 'SELECT caves.*, landowners.name AS landowner_name
            FROM caves
            LEFT JOIN landowners ON landowners.id = caves.landowner_id
            WHERE caves.grotto_id = :grotto_id';
        if (!$includeInactive) {
            $sql .= ' AND caves.active = 1';
        }
        $sql .= ' ORDER BY caves.name';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['grotto_id' => $grottoId]);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findForGrotto(int $id, int $grottoId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM caves WHERE id = :id AND grotto_id = :grotto_id LIMIT 1');
        $stmt->execute(['id' => $id, 'grotto_id' => $grottoId]);
        $cave = $stmt->fetch();
        return $cave ?: null;
    }

    /** @param array<string, mixed> $data */
    public function create(int $grottoId, array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO caves
            (grotto_id, landowner_id, name, county, general_area, gps_latitude, gps_longitude,
             access_notes, access_directions, parking_notes, gate_code, sensitive_notes, active, created_at)
            VALUES (:grotto_id, :landowner_id, :name, :county, :general_area, :gps_latitude, :gps_longitude,
             :access_notes, :access_directions, :parking_notes, :gate_code, :sensitive_notes, :active, NOW())');

        $stmt->execute($this->bindData($grottoId, $data));
        return (int)$this->db->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, int $grottoId, array $data): void
    {
        $params = $this->bindData($grottoId, $data);
        $params['id'] = $id;

        $stmt = $this->db->prepare('UPDATE caves SET
            landowner_id = :landowner_id,
            name = :name,
            county = :county,
            general_area = :general_area,
            gps_latitude = :gps_latitude,
            gps_longitude = :gps_longitude,
            access_notes = :access_notes,
            access_directions = :access_directions,
            parking_notes = :parking_notes,
            gate_code = :gate_code,
            sensitive_notes = :sensitive_notes,
            active = :active,
            updated_at = NOW()
            WHERE id = :id AND grotto_id = :grotto_id');

        $stmt->execute($params);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function bindData(int $grottoId, array $data): array
    {
        return [
            'grotto_id' => $grottoId,
            'landowner_id' => $this->nullableInt($data['landowner_id'] ?? null),
            'name' => $this->requiredString($data['name'] ?? '', 'Cave name is required.'),
            'county' => $this->nullableString($data['county'] ?? null),
            'general_area' => $this->nullableString($data['general_area'] ?? null),
            'gps_latitude' => $this->nullableDecimal($data['gps_latitude'] ?? null),
            'gps_longitude' => $this->nullableDecimal($data['gps_longitude'] ?? null),
            'access_notes' => $this->nullableString($data['access_notes'] ?? null),
            'access_directions' => $this->nullableString($data['access_directions'] ?? null),
            'parking_notes' => $this->nullableString($data['parking_notes'] ?? null),
            'gate_code' => $this->nullableString($data['gate_code'] ?? null),
            'sensitive_notes' => $this->nullableString($data['sensitive_notes'] ?? null),
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

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || trim((string)$value) === '') {
            return null;
        }
        return (int)$value;
    }

    private function nullableDecimal(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException('GPS coordinates must be numeric.');
        }
        return $value;
    }
}
