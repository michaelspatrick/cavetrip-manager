<?php

declare(strict_types=1);

return static function (PDO $db): void {
    $columnExists = static function (string $table, string $column) use ($db): bool {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND COLUMN_NAME = :column'
        );
        $stmt->execute(['table' => $table, 'column' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    };

    $tableExists = static function (string $table) use ($db): bool {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table'
        );
        $stmt->execute(['table' => $table]);
        return (int)$stmt->fetchColumn() > 0;
    };

    /* -----------------------------------------------------------------
       Repair cave schema expected by CaveService.
       Precise GPS coordinates remain intentionally excluded.
       ----------------------------------------------------------------- */
    if (!$columnExists('caves', 'state')) {
        $db->exec("ALTER TABLE caves ADD state VARCHAR(100) NULL AFTER name");
    }
    if (!$columnExists('caves', 'access_directions')) {
        $db->exec("ALTER TABLE caves ADD access_directions TEXT NULL AFTER access_notes");
    }
    if (!$columnExists('caves', 'parking_notes')) {
        $db->exec("ALTER TABLE caves ADD parking_notes TEXT NULL AFTER access_directions");
    }
    if (!$columnExists('caves', 'gate_code')) {
        $db->exec("ALTER TABLE caves ADD gate_code VARCHAR(255) NULL AFTER parking_notes");
    }

    foreach (['general_area', 'gps_latitude', 'gps_longitude'] as $column) {
        if ($columnExists('caves', $column)) {
            $db->exec("ALTER TABLE caves DROP COLUMN {$column}");
        }
    }

    /* -----------------------------------------------------------------
       Repair callout contacts table expected by TripController.
       ----------------------------------------------------------------- */
    $db->exec("CREATE TABLE IF NOT EXISTS trip_callout_contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trip_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NULL,
        phone VARCHAR(100) NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        INDEX idx_trip_callout_contacts_trip (trip_id, sort_order),
        CONSTRAINT fk_trip_callout_contacts_trip_v171
            FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    /* -----------------------------------------------------------------
       The original foundation already contained a trip_reports table
       with a different schema. v0.17.0 used CREATE TABLE IF NOT EXISTS,
       so that legacy table prevented the new schema from being created.

       Preserve the old table, create the v2 schema, then migrate any
       legacy reports that have enough trip/cave/user information.
       ----------------------------------------------------------------- */
    $legacyTable = 'trip_reports_legacy_v1';
    $needsUpgrade = $tableExists('trip_reports') && !$columnExists('trip_reports', 'grotto_id');

    if ($needsUpgrade) {
        if (!$tableExists($legacyTable)) {
            $db->exec("RENAME TABLE trip_reports TO {$legacyTable}");
        } else {
            // A previous repair preserved the legacy table already.
            // Remove the conflicting current table only if it is still legacy.
            $db->exec('DROP TABLE trip_reports');
        }
    }

    $db->exec("CREATE TABLE IF NOT EXISTS trip_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grotto_id INT NOT NULL,
        trip_id INT NOT NULL,
        cave_id INT NOT NULL,
        author_user_id INT NOT NULL,
        trip_leader_name VARCHAR(255) NULL,
        participant_names_json LONGTEXT NOT NULL,
        summary TEXT NOT NULL,
        conditions TEXT NULL,
        access_observations TEXT NULL,
        hazards TEXT NULL,
        incidents TEXT NULL,
        conservation_observations TEXT NULL,
        follow_up TEXT NULL,
        submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        UNIQUE KEY uq_trip_reports_trip_v2 (trip_id),
        INDEX idx_trip_reports_grotto_date_v2 (grotto_id, submitted_at),
        INDEX idx_trip_reports_cave_v2 (cave_id),
        INDEX idx_trip_reports_author_v2 (author_user_id),
        CONSTRAINT fk_trip_reports_grotto_v2 FOREIGN KEY (grotto_id) REFERENCES grottos(id) ON DELETE CASCADE,
        CONSTRAINT fk_trip_reports_trip_v2 FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
        CONSTRAINT fk_trip_reports_cave_v2 FOREIGN KEY (cave_id) REFERENCES caves(id) ON DELETE RESTRICT,
        CONSTRAINT fk_trip_reports_author_v2 FOREIGN KEY (author_user_id) REFERENCES users(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if ($tableExists($legacyTable)) {
        $legacyRows = $db->query("SELECT * FROM {$legacyTable} ORDER BY id")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $tripStmt = $db->prepare(
            'SELECT t.*, u.name AS leader_name
             FROM trips t
             LEFT JOIN users u ON u.id = t.trip_leader_user_id
             WHERE t.id = :id
             LIMIT 1'
        );
        $participantStmt = $db->prepare(
            "SELECT name
             FROM trip_participants
             WHERE trip_id = :trip_id
               AND participant_status NOT IN ('cancelled','removed')
             ORDER BY id"
        );
        $fallbackUserStmt = $db->prepare(
            "SELECT id
             FROM users
             WHERE grotto_id = :grotto_id
               AND active = 1
               AND role IN ('super_admin','admin','member')
             ORDER BY FIELD(role,'super_admin','admin','member'), id
             LIMIT 1"
        );
        $existsStmt = $db->prepare('SELECT id FROM trip_reports WHERE trip_id = :trip_id LIMIT 1');
        $insertStmt = $db->prepare(
            'INSERT INTO trip_reports (
                grotto_id, trip_id, cave_id, author_user_id,
                trip_leader_name, participant_names_json,
                summary, conditions, access_observations, hazards,
                incidents, conservation_observations, follow_up,
                submitted_at, updated_at
             ) VALUES (
                :grotto_id, :trip_id, :cave_id, :author_user_id,
                :trip_leader_name, :participant_names_json,
                :summary, :conditions, :access_observations, :hazards,
                :incidents, NULL, :follow_up,
                :submitted_at, :updated_at
             )'
        );

        foreach ($legacyRows as $legacy) {
            $tripId = (int)($legacy['trip_id'] ?? 0);
            if ($tripId <= 0) {
                continue;
            }

            $existsStmt->execute(['trip_id' => $tripId]);
            if ($existsStmt->fetchColumn()) {
                continue;
            }

            $tripStmt->execute(['id' => $tripId]);
            $trip = $tripStmt->fetch(PDO::FETCH_ASSOC);
            if (!$trip) {
                continue;
            }

            $grottoId = (int)($trip['grotto_id'] ?? 0);
            $caveId = (int)($trip['cave_id'] ?? 0);
            if ($grottoId <= 0 || $caveId <= 0) {
                // Keep the row safely in the legacy table rather than
                // inventing a cave association for historical data.
                continue;
            }

            $authorUserId = (int)($legacy['submitted_by_user_id'] ?? 0);
            if ($authorUserId <= 0) {
                $authorUserId = (int)($trip['trip_leader_user_id'] ?? 0);
            }
            if ($authorUserId <= 0) {
                $fallbackUserStmt->execute(['grotto_id' => $grottoId]);
                $authorUserId = (int)($fallbackUserStmt->fetchColumn() ?: 0);
            }
            if ($authorUserId <= 0) {
                continue;
            }

            $participantStmt->execute(['trip_id' => $tripId]);
            $participantNames = array_values(array_filter(array_map(
                static fn(array $row): string => trim((string)($row['name'] ?? '')),
                $participantStmt->fetchAll(PDO::FETCH_ASSOC) ?: []
            )));

            $summary = trim((string)($legacy['report_text'] ?? ''));
            if ($summary === '') {
                $summary = 'Legacy trip report imported from an earlier CaveTrip Manager schema.';
            }

            $accessParts = [];
            if (trim((string)($legacy['access_notes'] ?? '')) !== '') {
                $accessParts[] = trim((string)$legacy['access_notes']);
            }
            if (trim((string)($legacy['landowner_notes'] ?? '')) !== '') {
                $accessParts[] = 'Landowner notes: ' . trim((string)$legacy['landowner_notes']);
            }

            $insertStmt->execute([
                'grotto_id' => $grottoId,
                'trip_id' => $tripId,
                'cave_id' => $caveId,
                'author_user_id' => $authorUserId,
                'trip_leader_name' => trim((string)($trip['leader_name'] ?? '')) ?: null,
                'participant_names_json' => json_encode($participantNames, JSON_THROW_ON_ERROR),
                'summary' => $summary,
                'conditions' => trim((string)($legacy['conditions'] ?? '')) ?: null,
                'access_observations' => $accessParts === [] ? null : implode("\n\n", $accessParts),
                'hazards' => trim((string)($legacy['hazards'] ?? '')) ?: null,
                'incidents' => trim((string)($legacy['incidents'] ?? '')) ?: null,
                'follow_up' => trim((string)($legacy['follow_up_needed'] ?? '')) ?: null,
                'submitted_at' => $legacy['completed_at'] ?? $legacy['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $legacy['updated_at'] ?? null,
            ]);
        }
    }
};
