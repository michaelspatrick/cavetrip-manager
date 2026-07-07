<?php

declare(strict_types=1);


return static function (PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS waiver_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grotto_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        description TEXT NULL,
        html_body MEDIUMTEXT NOT NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        UNIQUE KEY unique_grotto_template_slug (grotto_id, slug),
        CONSTRAINT fk_waiver_templates_grotto FOREIGN KEY (grotto_id) REFERENCES grottos(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS trips (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grotto_id INT NOT NULL,
        trip_number VARCHAR(50) NOT NULL UNIQUE,
        title VARCHAR(255) NOT NULL,
        trip_date DATE NOT NULL,
        meeting_time TIME NULL,
        meeting_location VARCHAR(500) NULL,
        cave_id INT NULL,
        landowner_id INT NULL,
        waiver_template_id INT NULL,
        cave_description TEXT NULL,
        trip_leader_user_id INT NULL,
        min_attendees INT NULL,
        max_attendees INT NULL,
        signup_opens_at DATETIME NULL,
        signup_closes_at DATETIME NULL,
        visibility ENUM('core_group','selected_members','invite_link','private') NOT NULL DEFAULT 'core_group',
        waitlist_enabled TINYINT(1) NOT NULL DEFAULT 1,
        share_token CHAR(64) NULL UNIQUE,
        callout_time DATETIME NULL,
        callout_status ENUM('pending','all_out_safe','delayed_ok','emergency','overdue','cancelled') NOT NULL DEFAULT 'pending',
        callout_resolved_at DATETIME NULL,
        status ENUM('draft','open','waiver_signing','finalized','active','completed','cancelled') NOT NULL DEFAULT 'draft',
        cancelled_at DATETIME NULL,
        cancelled_by_user_id INT NULL,
        cancellation_reason TEXT NULL,
        notes TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        INDEX idx_trips_grotto_status_date (grotto_id, status, trip_date),
        INDEX idx_trips_leader (trip_leader_user_id),
        CONSTRAINT fk_trips_grotto FOREIGN KEY (grotto_id) REFERENCES grottos(id),
        CONSTRAINT fk_trips_cave FOREIGN KEY (cave_id) REFERENCES caves(id),
        CONSTRAINT fk_trips_landowner FOREIGN KEY (landowner_id) REFERENCES landowners(id),
        CONSTRAINT fk_trips_waiver_template FOREIGN KEY (waiver_template_id) REFERENCES waiver_templates(id),
        CONSTRAINT fk_trips_leader FOREIGN KEY (trip_leader_user_id) REFERENCES users(id),
        CONSTRAINT fk_trips_cancelled_by FOREIGN KEY (cancelled_by_user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS trip_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trip_id INT NOT NULL,
        user_id INT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(100) NULL,
        participant_status ENUM('registered','waitlisted','signed','cancelled','removed') NOT NULL DEFAULT 'registered',
        emergency_contact_name VARCHAR(255) NOT NULL,
        emergency_contact_phone VARCHAR(100) NOT NULL,
        emergency_contact_relationship VARCHAR(100) NULL,
        medical_notes TEXT NULL,
        allergies TEXT NULL,
        medications TEXT NULL,
        conditions TEXT NULL,
        physical_limitations TEXT NULL,
        is_minor TINYINT(1) NOT NULL DEFAULT 0,
        guardian_name VARCHAR(255) NULL,
        guardian_email VARCHAR(255) NULL,
        signed_at DATETIME NULL,
        signature_data MEDIUMTEXT NULL,
        signed_ip VARCHAR(100) NULL,
        user_agent TEXT NULL,
        exited_safely_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        INDEX idx_trip_participants_trip_status (trip_id, participant_status),
        INDEX idx_trip_participants_email (email),
        CONSTRAINT fk_trip_participants_trip FOREIGN KEY (trip_id) REFERENCES trips(id),
        CONSTRAINT fk_trip_participants_user FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS generated_waivers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trip_id INT NOT NULL,
        waiver_template_id INT NOT NULL,
        public_token CHAR(64) NOT NULL UNIQUE,
        final_html MEDIUMTEXT NOT NULL,
        finalized_by_user_id INT NULL,
        finalized_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        emailed_at DATETIME NULL,
        CONSTRAINT fk_generated_waivers_trip FOREIGN KEY (trip_id) REFERENCES trips(id),
        CONSTRAINT fk_generated_waivers_template FOREIGN KEY (waiver_template_id) REFERENCES waiver_templates(id),
        CONSTRAINT fk_generated_waivers_user FOREIGN KEY (finalized_by_user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
