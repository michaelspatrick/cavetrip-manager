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

    public function tripCreated(int $grottoId, int $userId, int $tripId): void
    {
        $this->record('trip.created', 'Trip created.', $grottoId, $userId, 'trip', $tripId);
    }

    public function tripUpdated(int $grottoId, int $userId, int $tripId): void
    {
        $this->record('trip.updated', 'Trip updated.', $grottoId, $userId, 'trip', $tripId);
    }

    public function tripCancelled(int $grottoId, int $userId, int $tripId, string $reason = ''): void
    {
        $this->record('trip.cancelled', 'Trip cancelled.', $grottoId, $userId, 'trip', $tripId, [
            'reason' => $reason,
        ]);
    }

    public function participantAdded(int $grottoId, int $userId, int $tripId, int $participantId): void
    {
        $this->record('trip_participant.added', 'Participant added to trip.', $grottoId, $userId, 'trip_participant', $participantId, [
            'trip_id' => $tripId,
        ]);
    }

    public function participantRemoved(int $grottoId, int $userId, int $tripId, int $participantId): void
    {
        $this->record('trip_participant.removed', 'Participant removed from trip.', $grottoId, $userId, 'trip_participant', $participantId, [
            'trip_id' => $tripId,
        ]);
    }

    public function waiverTemplateCreated(int $grottoId, int $userId, int $templateId): void
    {
        $this->record('waiver_template.created', 'Waiver template created.', $grottoId, $userId, 'waiver_template', $templateId);
    }

    public function waiverTemplateUpdated(int $grottoId, int $userId, int $templateId): void
    {
        $this->record('waiver_template.updated', 'Waiver template updated.', $grottoId, $userId, 'waiver_template', $templateId);
    }

    public function userLoggedIn(int $grottoId, int $userId): void
    {
        $this->record('auth.login', 'User logged in.', $grottoId, $userId, 'user', $userId);
    }

    public function userLoggedOut(int $grottoId, int $userId): void
    {
        $this->record('auth.logout', 'User logged out.', $grottoId, $userId, 'user', $userId);
    }

    private function record(
        string $eventType,
        string $message,
        ?int $grottoId = null,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        array $metadata = []
    ): void {
        $stmt = $this->app->db()->prepare("
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
