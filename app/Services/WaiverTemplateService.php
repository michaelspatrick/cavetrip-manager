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
    public function listActiveForGrotto(int $grottoId): array
    {
        $stmt = $this->db->prepare('SELECT id, name FROM waiver_templates WHERE grotto_id = :grotto_id AND active = 1 ORDER BY name');
        $stmt->execute(['grotto_id' => $grottoId]);
        return $stmt->fetchAll();
    }
}
