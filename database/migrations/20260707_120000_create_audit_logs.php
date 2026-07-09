<?php

declare(strict_types=1);

use PDO;

return static function (PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            grotto_id INT NULL,
            user_id INT NULL,
            event_type VARCHAR(100) NOT NULL,
            entity_type VARCHAR(100) NULL,
            entity_id INT NULL,
            message TEXT NOT NULL,
            metadata_json JSON NULL,
            ip_address VARCHAR(100) NULL,
            user_agent TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            INDEX idx_audit_logs_grotto_id (grotto_id),
            INDEX idx_audit_logs_user_id (user_id),
            INDEX idx_audit_logs_event_type (event_type),
            INDEX idx_audit_logs_entity (entity_type, entity_id),
            INDEX idx_audit_logs_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
};
