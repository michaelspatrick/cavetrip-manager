<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use PDO;
use CaveTrip\Services\TokenService;

final class TripParticipantService
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listForTrip(int $tripId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM trip_participants WHERE trip_id = :trip_id ORDER BY FIELD(participant_status, \'registered\',\'signed\',\'waitlisted\',\'cancelled\',\'removed\'), created_at ASC, id ASC');
        $stmt->execute(['trip_id' => $tripId]);
        return $stmt->fetchAll();
    }

    public function countActiveForTrip(int $tripId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM trip_participants WHERE trip_id = :trip_id AND participant_status IN (\'registered\', \'signed\')');
        $stmt->execute(['trip_id' => $tripId]);
        return (int)$stmt->fetchColumn();
    }

    /** @param array<string, mixed> $trip @param array<string, mixed> $data */
    public function addParticipant(array $trip, array $data, ?int $userId = null): int
    {
        $tripId = (int)$trip['id'];
        $maxAttendees = $trip['max_attendees'] === null ? null : (int)$trip['max_attendees'];
        $waitlistEnabled = (int)($trip['waitlist_enabled'] ?? 1) === 1;
        $activeCount = $this->countActiveForTrip($tripId);

        $status = 'registered';
        if ($maxAttendees !== null && $maxAttendees > 0 && $activeCount >= $maxAttendees) {
            if (!$waitlistEnabled) {
                throw new \InvalidArgumentException('This trip is full and the waitlist is closed.');
            }
            $status = 'waitlisted';
        }

        $isMinor = isset($data['is_minor']) ? 1 : 0;
        if ($isMinor === 1 && trim((string)($data['guardian_name'] ?? '')) === '') {
            throw new \InvalidArgumentException('Guardian name is required for minors.');
        }

        $stmt = $this->db->prepare('INSERT INTO trip_participants
            (trip_id, user_id, name, email, phone, participant_status, signature_token,
             emergency_contact_name, emergency_contact_phone, emergency_contact_relationship,
             medical_notes, allergies, medications, conditions, physical_limitations,
             is_minor, guardian_name, guardian_email, created_at)
            VALUES
            (:trip_id, :user_id, :name, :email, :phone, :participant_status, :signature_token,
             :emergency_contact_name, :emergency_contact_phone, :emergency_contact_relationship,
             :medical_notes, :allergies, :medications, :conditions, :physical_limitations,
             :is_minor, :guardian_name, :guardian_email, NOW())');
        $stmt->execute([
            'trip_id' => $tripId,
            'user_id' => $userId,
            'name' => $this->requiredString($data['name'] ?? '', 'Participant name is required.'),
            'email' => $this->requiredEmail($data['email'] ?? ''),
            'phone' => $this->nullableString($data['phone'] ?? null),
            'participant_status' => $status,
            'signature_token' => TokenService::make(),
            'emergency_contact_name' => $this->requiredString($data['emergency_contact_name'] ?? '', 'Emergency contact name is required.'),
            'emergency_contact_phone' => $this->requiredString($data['emergency_contact_phone'] ?? '', 'Emergency contact phone is required.'),
            'emergency_contact_relationship' => $this->nullableString($data['emergency_contact_relationship'] ?? null),
            'medical_notes' => $this->nullableString($data['medical_notes'] ?? null),
            'allergies' => $this->nullableString($data['allergies'] ?? null),
            'medications' => $this->nullableString($data['medications'] ?? null),
            'conditions' => $this->nullableString($data['conditions'] ?? null),
            'physical_limitations' => $this->nullableString($data['physical_limitations'] ?? null),
            'is_minor' => $isMinor,
            'guardian_name' => $this->nullableString($data['guardian_name'] ?? null),
            'guardian_email' => $this->nullableEmail($data['guardian_email'] ?? null),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function removeParticipant(int $participantId, int $tripId): void
    {
        $stmt = $this->db->prepare('UPDATE trip_participants SET participant_status = \'removed\', updated_at = NOW() WHERE id = :id AND trip_id = :trip_id');
        $stmt->execute(['id' => $participantId, 'trip_id' => $tripId]);
    }

    public function cancelSignup(int $participantId, int $tripId, string $email): void
    {
        $stmt = $this->db->prepare('UPDATE trip_participants SET participant_status = \'cancelled\', updated_at = NOW() WHERE id = :id AND trip_id = :trip_id AND email = :email');
        $stmt->execute(['id' => $participantId, 'trip_id' => $tripId, 'email' => strtolower(trim($email))]);
    }



    /** @return array<string, mixed>|null */
    public function findBySignatureToken(string $token): ?array
    {
        $stmt = $this->db->prepare('SELECT tp.*, t.title AS trip_title, t.trip_date, t.meeting_time, t.cave_description, c.name AS cave_name
            FROM trip_participants tp
            INNER JOIN trips t ON t.id = tp.trip_id
            LEFT JOIN caves c ON c.id = t.cave_id
            WHERE tp.signature_token = :signature_token
            LIMIT 1');
        $stmt->execute(['signature_token' => trim($token)]);
        $participant = $stmt->fetch();
        return $participant ?: null;
    }

    public function saveSignature(string $token, string $signatureData, ?string $ip, ?string $userAgent): void
    {
        $token = trim($token);
        if ($token === '') {
            throw new \InvalidArgumentException('Invalid signature link.');
        }
        if (!str_starts_with($signatureData, 'data:image/png;base64,')) {
            throw new \InvalidArgumentException('Please sign before submitting.');
        }
        if (strlen($signatureData) > 750000) {
            throw new \InvalidArgumentException('Signature image is too large. Please clear and sign again.');
        }

        $stmt = $this->db->prepare('UPDATE trip_participants SET
            signature_data = :signature_data,
            signed_at = NOW(),
            signed_ip = :signed_ip,
            user_agent = :user_agent,
            participant_status = CASE WHEN participant_status = \'registered\' THEN \'signed\' ELSE participant_status END,
            updated_at = NOW()
            WHERE signature_token = :signature_token');
        $stmt->execute([
            'signature_data' => $signatureData,
            'signed_ip' => $ip,
            'user_agent' => $userAgent,
            'signature_token' => $token,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new \InvalidArgumentException('Signature link was not found.');
        }
    }

    public function ensureSignatureTokensForTrip(int $tripId): void
    {
        $stmt = $this->db->prepare('SELECT id FROM trip_participants WHERE trip_id = :trip_id AND signature_token IS NULL');
        $stmt->execute(['trip_id' => $tripId]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $update = $this->db->prepare('UPDATE trip_participants SET signature_token = :signature_token WHERE id = :id');
        foreach ($ids as $id) {
            $update->execute(['signature_token' => TokenService::make(), 'id' => (int)$id]);
        }
    }

    private function requiredString(mixed $value, string $message): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            throw new \InvalidArgumentException($message);
        }
        return $value;
    }

    private function requiredEmail(mixed $value): string
    {
        $email = strtolower(trim((string)$value));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A valid email address is required.');
        }
        return $email;
    }

    private function nullableEmail(mixed $value): ?string
    {
        $email = strtolower(trim((string)$value));
        if ($email === '') {
            return null;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Guardian email must be valid when provided.');
        }
        return $email;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }
}
