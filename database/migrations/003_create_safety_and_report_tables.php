<?php

declare(strict_types=1);


return static function (PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS trip_callout_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trip_id INT NOT NULL,
        reported_by_user_id INT NULL,
        event_type ENUM('all_out_safe','delayed_ok','emergency','overdue','cancelled') NOT NULL,
        notes TEXT NULL,
        new_callout_time DATETIME NULL,
        reported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        reported_ip VARCHAR(100) NULL,
        user_agent TEXT NULL,
        INDEX idx_callout_trip_time (trip_id, reported_at),
        CONSTRAINT fk_callout_events_trip FOREIGN KEY (trip_id) REFERENCES trips(id),
        CONSTRAINT fk_callout_events_user FOREIGN KEY (reported_by_user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS emergency_packets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trip_id INT NOT NULL,
        generated_by_user_id INT NULL,
        packet_token CHAR(64) NOT NULL UNIQUE,
        generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_viewed_at DATETIME NULL,
        access_count INT NOT NULL DEFAULT 0,
        CONSTRAINT fk_emergency_packets_trip FOREIGN KEY (trip_id) REFERENCES trips(id),
        CONSTRAINT fk_emergency_packets_user FOREIGN KEY (generated_by_user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS trip_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trip_id INT NOT NULL,
        submitted_by_user_id INT NULL,
        report_text MEDIUMTEXT NULL,
        conditions TEXT NULL,
        hazards TEXT NULL,
        access_notes TEXT NULL,
        landowner_notes TEXT NULL,
        incidents TEXT NULL,
        follow_up_needed TEXT NULL,
        completed_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        CONSTRAINT fk_trip_reports_trip FOREIGN KEY (trip_id) REFERENCES trips(id),
        CONSTRAINT fk_trip_reports_user FOREIGN KEY (submitted_by_user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS trip_report_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trip_id INT NOT NULL,
        user_id INT NULL,
        note_text TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_trip_report_notes_trip FOREIGN KEY (trip_id) REFERENCES trips(id),
        CONSTRAINT fk_trip_report_notes_user FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
