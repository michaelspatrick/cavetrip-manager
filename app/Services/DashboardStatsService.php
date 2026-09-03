<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use PDO;

final class DashboardStatsService
{
    public function __construct(private readonly PDO $db) {}

    /** @param array<string,mixed> $user @return array<string,int> */
    public function countsForUser(array $user): array
    {
        $role = (string)($user['role'] ?? 'guest');
        $userId = (int)($user['id'] ?? 0);
        $grottoId = (int)($user['grotto_id'] ?? 0);

        if ($role === 'guest') {
            $scope = "EXISTS (
                SELECT 1 FROM trip_participants my_tp
                WHERE my_tp.trip_id = t.id
                  AND my_tp.user_id = :user_id
                  AND my_tp.participant_status NOT IN ('removed','cancelled')
            )";
            $params = ['user_id' => $userId];

            return [
                'upcoming_trips' => $this->scalar("SELECT COUNT(*) FROM trips t WHERE t.trip_date >= CURDATE() AND t.status NOT IN ('completed','cancelled') AND {$scope}", $params),
                'participants' => 0,
                'waivers_pending' => $this->scalar("SELECT COUNT(*) FROM trip_participants tp INNER JOIN trips t ON t.id=tp.trip_id WHERE tp.user_id=:user_id AND t.trip_date >= CURDATE() AND t.status NOT IN ('completed','cancelled') AND tp.participant_status NOT IN ('removed','cancelled','waitlisted') AND tp.signed_at IS NULL", $params),
                'reports_needed' => 0,
                'caves' => 0,
            ];
        }

        [$scopeSql, $params] = $this->grottoScope('t', $role, $grottoId);
        [$caveScope, $caveParams] = $this->grottoScope('c', $role, $grottoId);

        return [
            'upcoming_trips' => $this->scalar("SELECT COUNT(*) FROM trips t WHERE t.trip_date >= CURDATE() AND t.status NOT IN ('completed','cancelled') {$scopeSql}", $params),
            'participants' => $this->scalar("SELECT COUNT(*) FROM trip_participants tp INNER JOIN trips t ON t.id=tp.trip_id WHERE t.trip_date >= CURDATE() AND t.status NOT IN ('completed','cancelled') AND tp.participant_status IN ('registered','signed') {$scopeSql}", $params),
            'waivers_pending' => $this->scalar("SELECT COUNT(*) FROM trip_participants tp INNER JOIN trips t ON t.id=tp.trip_id WHERE t.trip_date >= CURDATE() AND t.status NOT IN ('completed','cancelled') AND tp.participant_status IN ('registered','signed') AND tp.signed_at IS NULL {$scopeSql}", $params),
            'reports_needed' => $this->scalar("SELECT COUNT(*) FROM trips t LEFT JOIN trip_reports r ON r.trip_id=t.id WHERE t.status='completed' AND r.id IS NULL {$scopeSql}", $params),
            'caves' => $this->scalar("SELECT COUNT(*) FROM caves c WHERE 1=1 {$caveScope}", $caveParams),
        ];
    }

    /** @param array<string,mixed> $user @return array<int,array<string,mixed>> */
    public function upcomingTrips(array $user, int $limit = 5): array
    {
        $role = (string)($user['role'] ?? 'guest');
        $userId = (int)($user['id'] ?? 0);
        $grottoId = (int)($user['grotto_id'] ?? 0);
        $limit = max(1, min(10, $limit));

        $where = "t.trip_date >= CURDATE() AND t.status NOT IN ('completed','cancelled')";
        $params = [];

        if ($role === 'guest') {
            $where .= " AND EXISTS (
                SELECT 1 FROM trip_participants my_tp
                WHERE my_tp.trip_id=t.id
                  AND my_tp.user_id=:user_id
                  AND my_tp.participant_status NOT IN ('removed','cancelled')
            )";
            $params['user_id'] = $userId;
        } elseif ($role !== 'super_admin' || $grottoId > 0) {
            $where .= ' AND t.grotto_id=:grotto_id';
            $params['grotto_id'] = $grottoId;
        }

        $sql = "SELECT t.id,t.title,t.trip_date,t.meeting_time,t.status,t.max_attendees,t.signup_closes_at,
                       c.name AS cave_name,u.name AS leader_name,
                       (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id=t.id AND tp.participant_status IN ('registered','signed')) AS registered_count,
                       (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id=t.id AND tp.participant_status IN ('registered','signed') AND tp.signed_at IS NOT NULL) AS signed_count
                FROM trips t
                LEFT JOIN caves c ON c.id=t.cave_id
                LEFT JOIN users u ON u.id=t.trip_leader_user_id
                WHERE {$where}
                ORDER BY t.trip_date ASC, t.meeting_time ASC, t.id ASC
                LIMIT {$limit}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array{0:string,1:array<string,int>} */
    private function grottoScope(string $alias, string $role, int $grottoId): array
    {
        if ($role === 'super_admin' && $grottoId <= 0) {
            return ['', []];
        }
        if ($grottoId <= 0) {
            return [' AND 1=0', []];
        }
        return [" AND {$alias}.grotto_id=:grotto_id", ['grotto_id' => $grottoId]];
    }

    /** @param array<string,mixed> $params */
    private function scalar(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}
