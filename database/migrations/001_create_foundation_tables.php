<?php

declare(strict_types=1);


return static function (PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS grottos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(255) NULL,
        phone VARCHAR(100) NULL,
        website_url VARCHAR(255) NULL,
        mailing_address TEXT NULL,
        contact_name VARCHAR(255) NULL,
        logo_url VARCHAR(500) NULL,
        logo_file_path VARCHAR(500) NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grotto_id INT NULL,
        role ENUM('super_admin','grotto_admin','member','guest') NOT NULL DEFAULT 'guest',
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        phone VARCHAR(100) NULL,
        password_hash VARCHAR(255) NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        INDEX idx_users_grotto_role (grotto_id, role),
        CONSTRAINT fk_users_grotto FOREIGN KEY (grotto_id) REFERENCES grottos(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS landowners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grotto_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NULL,
        phone VARCHAR(100) NULL,
        mailing_address TEXT NULL,
        preferred_contact_method VARCHAR(100) NULL,
        notes TEXT NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        INDEX idx_landowners_grotto_name (grotto_id, name),
        CONSTRAINT fk_landowners_grotto FOREIGN KEY (grotto_id) REFERENCES grottos(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS caves (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grotto_id INT NOT NULL,
        landowner_id INT NULL,
        name VARCHAR(255) NOT NULL,
        county VARCHAR(255) NULL,
        general_area VARCHAR(255) NULL,
        gps_latitude DECIMAL(10,7) NULL,
        gps_longitude DECIMAL(10,7) NULL,
        access_notes TEXT NULL,
        sensitive_notes TEXT NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        INDEX idx_caves_grotto_name (grotto_id, name),
        CONSTRAINT fk_caves_grotto FOREIGN KEY (grotto_id) REFERENCES grottos(id),
        CONSTRAINT fk_caves_landowner FOREIGN KEY (landowner_id) REFERENCES landowners(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS notification_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grotto_id INT NOT NULL,
        email_from_name VARCHAR(255) NULL,
        email_from_address VARCHAR(255) NULL,
        pushover_enabled TINYINT(1) NOT NULL DEFAULT 0,
        pushover_app_token VARCHAR(255) NULL,
        pushover_user_key VARCHAR(255) NULL,
        pushover_emergency_priority INT NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        CONSTRAINT fk_notification_settings_grotto FOREIGN KEY (grotto_id) REFERENCES grottos(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS audit_log (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        grotto_id INT NULL,
        user_id INT NULL,
        action VARCHAR(100) NOT NULL,
        entity_type VARCHAR(100) NOT NULL,
        entity_id INT NULL,
        metadata_json JSON NULL,
        ip_address VARCHAR(100) NULL,
        user_agent TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_audit_grotto_created (grotto_id, created_at),
        INDEX idx_audit_entity (entity_type, entity_id),
        CONSTRAINT fk_audit_grotto FOREIGN KEY (grotto_id) REFERENCES grottos(id),
        CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
