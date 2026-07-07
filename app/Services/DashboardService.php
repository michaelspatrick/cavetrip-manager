<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use PDO;

final class DashboardService
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return array<string, int> */
    public function statsForUser(array $user): array
    {
        $grottoId = ($user['role'] ?? '') === 'super_admin' ? null : (int)($user['grotto_id'] ?? 0);

        return [
            'grottos' => $this->countRows('grottos', null),
            'users' => $this->countRows('users', $grottoId),
            'landowners' => $this->countRows('landowners', $grottoId),
            'caves' => $this->countRows('caves', $grottoId),
            'trips' => $this->countRows('trips', $grottoId),
        ];
    }

    private function countRows(string $table, ?int $grottoId): int
    {
        $allowedTables = ['grottos', 'users', 'landowners', 'caves', 'trips'];
        if (!in_array($table, $allowedTables, true)) {
            throw new \InvalidArgumentException('Unsupported dashboard table.');
        }

        if ($table === 'grottos' || $grottoId === null) {
            return (int)$this->db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE grotto_id = :grotto_id");
        $stmt->execute(['grotto_id' => $grottoId]);
        return (int)$stmt->fetchColumn();
    }
}
