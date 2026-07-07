<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use PDO;

final class AuditLogService
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function record(?int $grottoId, ?int $userId, string $action, string $entityType, ?int $entityId = null, array $metadata = []): void
    {
        $stmt = $this->db->prepare('INSERT INTO audit_log
            (grotto_id, user_id, action, entity_type, entity_id, metadata_json, ip_address, user_agent, created_at)
            VALUES (:grotto_id, :user_id, :action, :entity_type, :entity_id, :metadata_json, :ip_address, :user_agent, NOW())');

        $stmt->execute([
            'grotto_id' => $grottoId,
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata_json' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
