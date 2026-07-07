<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use PDO;

final class TripService
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listForGrotto(int $grottoId): array
    {
        $stmt = $this->db->prepare('SELECT trips.*, grottos.name AS grotto_name, caves.name AS cave_name, landowners.name AS landowner_name, users.name AS leader_name,
                (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id = trips.id AND tp.participant_status IN (\'registered\',\'signed\')) AS registered_count,
                (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id = trips.id AND tp.participant_status = \'waitlisted\') AS waitlist_count,
                (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id = trips.id AND tp.signed_at IS NOT NULL AND tp.participant_status IN (\'registered\',\'signed\')) AS signed_count
            FROM trips
            INNER JOIN grottos ON grottos.id = trips.grotto_id
            LEFT JOIN caves ON caves.id = trips.cave_id
            LEFT JOIN landowners ON landowners.id = trips.landowner_id
            LEFT JOIN users ON users.id = trips.trip_leader_user_id
            WHERE trips.grotto_id = :grotto_id
            ORDER BY trips.trip_date DESC, trips.meeting_time DESC, trips.id DESC');
        $stmt->execute(['grotto_id' => $grottoId]);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findForGrotto(int $id, int $grottoId): ?array
    {
        $stmt = $this->db->prepare('SELECT trips.*, grottos.name AS grotto_name, caves.name AS cave_name, landowners.name AS landowner_name, users.name AS leader_name,
                (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id = trips.id AND tp.participant_status IN (\'registered\',\'signed\')) AS registered_count,
                (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id = trips.id AND tp.participant_status = \'waitlisted\') AS waitlist_count,
                (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id = trips.id AND tp.signed_at IS NOT NULL AND tp.participant_status IN (\'registered\',\'signed\')) AS signed_count
            FROM trips
            INNER JOIN grottos ON grottos.id = trips.grotto_id
            LEFT JOIN caves ON caves.id = trips.cave_id
            LEFT JOIN landowners ON landowners.id = trips.landowner_id
            LEFT JOIN users ON users.id = trips.trip_leader_user_id
            WHERE trips.id = :id AND trips.grotto_id = :grotto_id
            LIMIT 1');
        $stmt->execute(['id' => $id, 'grotto_id' => $grottoId]);
        $trip = $stmt->fetch();
        return $trip ?: null;
    }



    /** @return array<string, mixed>|null */
    public function findByShareToken(string $token): ?array
    {
        $stmt = $this->db->prepare('SELECT trips.*, grottos.name AS grotto_name, caves.name AS cave_name, landowners.name AS landowner_name, users.name AS leader_name,
                (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id = trips.id AND tp.participant_status IN (\'registered\',\'signed\')) AS registered_count,
                (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id = trips.id AND tp.participant_status = \'waitlisted\') AS waitlist_count,
                (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id = trips.id AND tp.signed_at IS NOT NULL AND tp.participant_status IN (\'registered\',\'signed\')) AS signed_count
            FROM trips
            INNER JOIN grottos ON grottos.id = trips.grotto_id
            LEFT JOIN caves ON caves.id = trips.cave_id
            LEFT JOIN landowners ON landowners.id = trips.landowner_id
            LEFT JOIN users ON users.id = trips.trip_leader_user_id
            WHERE trips.share_token = :share_token
            LIMIT 1');
        $stmt->execute(['share_token' => trim($token)]);
        $trip = $stmt->fetch();
        return $trip ?: null;
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $currentUser */
    public function create(int $grottoId, array $data, array $currentUser): int
    {
        $tripNumber = $this->nextTripNumber($grottoId);
        $params = $this->bindData($grottoId, $data, $currentUser, $tripNumber);

        $stmt = $this->db->prepare('INSERT INTO trips
            (grotto_id, trip_number, title, trip_date, meeting_time, meeting_location, cave_id, landowner_id,
             waiver_template_id, cave_description, trip_leader_user_id, min_attendees, max_attendees,
             signup_opens_at, signup_closes_at, visibility, waitlist_enabled, share_token,
             callout_time, status, notes, created_at)
            VALUES
            (:grotto_id, :trip_number, :title, :trip_date, :meeting_time, :meeting_location, :cave_id, :landowner_id,
             :waiver_template_id, :cave_description, :trip_leader_user_id, :min_attendees, :max_attendees,
             :signup_opens_at, :signup_closes_at, :visibility, :waitlist_enabled, :share_token,
             :callout_time, :status, :notes, NOW())');
        $stmt->execute($params);
        return (int)$this->db->lastInsertId();
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $currentUser */
    public function update(int $id, int $grottoId, array $data, array $currentUser): void
    {
        $existing = $this->findForGrotto($id, $grottoId);
        if ($existing === null) {
            throw new \InvalidArgumentException('Trip not found.');
        }

        $params = $this->bindData($grottoId, $data, $currentUser, (string)$existing['trip_number']);
        $params['id'] = $id;

        $stmt = $this->db->prepare('UPDATE trips SET
            title = :title,
            trip_date = :trip_date,
            meeting_time = :meeting_time,
            meeting_location = :meeting_location,
            cave_id = :cave_id,
            landowner_id = :landowner_id,
            waiver_template_id = :waiver_template_id,
            cave_description = :cave_description,
            trip_leader_user_id = :trip_leader_user_id,
            min_attendees = :min_attendees,
            max_attendees = :max_attendees,
            signup_opens_at = :signup_opens_at,
            signup_closes_at = :signup_closes_at,
            visibility = :visibility,
            waitlist_enabled = :waitlist_enabled,
            callout_time = :callout_time,
            status = :status,
            notes = :notes,
            updated_at = NOW()
            WHERE id = :id AND grotto_id = :grotto_id');
        unset($params['trip_number'], $params['share_token']);
        $stmt->execute($params);
    }

    public function cancel(int $id, int $grottoId, int $cancelledByUserId, string $reason): void
    {
        $stmt = $this->db->prepare('UPDATE trips SET
            status = \'cancelled\',
            callout_status = \'cancelled\',
            cancelled_at = NOW(),
            cancelled_by_user_id = :cancelled_by_user_id,
            cancellation_reason = :cancellation_reason,
            updated_at = NOW()
            WHERE id = :id AND grotto_id = :grotto_id');
        $stmt->execute([
            'id' => $id,
            'grotto_id' => $grottoId,
            'cancelled_by_user_id' => $cancelledByUserId,
            'cancellation_reason' => trim($reason),
        ]);
    }

    private function nextTripNumber(int $grottoId): string
    {
        $stmt = $this->db->prepare('SELECT slug FROM grottos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $grottoId]);
        $slug = (string)($stmt->fetchColumn() ?: 'ctm');

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM trips WHERE grotto_id = :grotto_id AND YEAR(created_at) = YEAR(CURDATE())');
        $stmt->execute(['grotto_id' => $grottoId]);
        $sequence = ((int)$stmt->fetchColumn()) + 1;

        return TripNumberService::generate($slug, $sequence);
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $currentUser @return array<string, mixed> */
    private function bindData(int $grottoId, array $data, array $currentUser, string $tripNumber): array
    {
        $min = $this->nullableInt($data['min_attendees'] ?? null);
        $max = $this->nullableInt($data['max_attendees'] ?? null);
        if ($min !== null && $max !== null && $min > $max) {
            throw new \InvalidArgumentException('Minimum attendees cannot be greater than maximum attendees.');
        }

        $visibility = (string)($data['visibility'] ?? 'core_group');
        if (!in_array($visibility, ['core_group', 'selected_members', 'invite_link', 'private'], true)) {
            $visibility = 'core_group';
        }

        $status = (string)($data['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'open', 'waiver_signing', 'finalized', 'active', 'completed', 'cancelled'], true)) {
            $status = 'draft';
        }

        return [
            'grotto_id' => $grottoId,
            'trip_number' => $tripNumber,
            'title' => $this->requiredString($data['title'] ?? '', 'Trip title is required.'),
            'trip_date' => $this->requiredDate($data['trip_date'] ?? '', 'Trip date is required.'),
            'meeting_time' => $this->nullableTime($data['meeting_time'] ?? null),
            'meeting_location' => $this->nullableString($data['meeting_location'] ?? null),
            'cave_id' => $this->nullableInt($data['cave_id'] ?? null),
            'landowner_id' => $this->nullableInt($data['landowner_id'] ?? null),
            'waiver_template_id' => $this->nullableInt($data['waiver_template_id'] ?? null),
            'cave_description' => $this->nullableString($data['cave_description'] ?? null),
            'trip_leader_user_id' => (int)$currentUser['id'],
            'min_attendees' => $min,
            'max_attendees' => $max,
            'signup_opens_at' => $this->nullableDateTime($data['signup_opens_at'] ?? null),
            'signup_closes_at' => $this->nullableDateTime($data['signup_closes_at'] ?? null),
            'visibility' => $visibility,
            'waitlist_enabled' => isset($data['waitlist_enabled']) ? 1 : 0,
            'share_token' => TokenService::make(),
            'callout_time' => $this->nullableDateTime($data['callout_time'] ?? null),
            'status' => $status,
            'notes' => $this->nullableString($data['notes'] ?? null),
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

    private function requiredDate(mixed $value, string $message): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            throw new \InvalidArgumentException($message);
        }
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new \InvalidArgumentException('Trip date must be YYYY-MM-DD.');
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

    private function nullableTime(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        return preg_match('/^\d{2}:\d{2}$/', $value) ? $value : null;
    }

    private function nullableDateTime(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $value = str_replace('T', ' ', $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            return $value . ':00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }
        throw new \InvalidArgumentException('Date/time fields must be valid.');
    }
}
