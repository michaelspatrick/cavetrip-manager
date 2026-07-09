<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use CaveTrip\Core\Application;
use PDO;

final class AuditLogService
{
    public function __construct(private readonly Application $app)
    {
    }

    public function record(
        string $eventType,
        string $message,
        ?int $grottoId = null,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        array $metadata = []
    ): void {
        $pdo = $this->app->db();

        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (
                grotto_id,
                user_id,
                event_type,
                entity_type,
                entity_id,
                message,
                metadata_json,
                ip_address,
                user_agent,
                created_at
            ) VALUES (
                :grotto_id,
                :user_id,
                :event_type,
                :entity_type,
                :entity_id,
                :message,
                :metadata_json,
                :ip_address,
                :user_agent,
                NOW()
            )
        ");

        $stmt->execute([
            'grotto_id' => $grottoId,
            'user_id' => $userId,
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'message' => $message,
            'metadata_json' => $metadata === [] ? null : json_encode($metadata, JSON_THROW_ON_ERROR),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    public function latest(int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));

        $stmt = $this->app->db()->prepare("
            SELECT *
            FROM audit_logs
            ORDER BY created_at DESC, id DESC
            LIMIT {$limit}
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
