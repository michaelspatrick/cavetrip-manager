<?php

declare(strict_types=1);


return static function (PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS trip_invites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trip_id INT NOT NULL,
        user_id INT NULL,
        email VARCHAR(255) NULL,
        invite_token CHAR(64) NOT NULL UNIQUE,
        status ENUM('pending','accepted','declined','expired') NOT NULL DEFAULT 'pending',
        sent_at DATETIME NULL,
        responded_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_trip_invites_trip FOREIGN KEY (trip_id) REFERENCES trips(id),
        CONSTRAINT fk_trip_invites_user FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS waiver_email_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        generated_waiver_id INT NOT NULL,
        recipient_email VARCHAR(255) NOT NULL,
        recipient_type ENUM('landowner','participant','guardian','grotto','trip_leader') NOT NULL,
        sent_at DATETIME NULL,
        status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
        error_message TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_waiver_email_status (status),
        CONSTRAINT fk_waiver_email_log_waiver FOREIGN KEY (generated_waiver_id) REFERENCES generated_waivers(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
