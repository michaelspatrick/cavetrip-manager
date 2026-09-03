<?php

declare(strict_types=1);

return static function (PDO $db): void {
    // Normalize legacy workflow-only statuses before simplifying the admin-facing model.
    $db->exec("UPDATE trips SET status = 'open' WHERE status IN ('waiver_signing','finalized','active')");
    $db->exec("ALTER TABLE trips MODIFY status ENUM('draft','open','closed','completed','cancelled') NOT NULL DEFAULT 'draft'");

    $db->exec("CREATE TABLE IF NOT EXISTS email_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grotto_id INT NOT NULL,
        provider ENUM('smtp','ses') NOT NULL DEFAULT 'smtp',
        from_name VARCHAR(255) NOT NULL,
        from_email VARCHAR(255) NOT NULL,
        reply_to_email VARCHAR(255) NULL,
        smtp_host VARCHAR(255) NULL,
        smtp_port INT NOT NULL DEFAULT 587,
        smtp_encryption ENUM('none','tls','ssl') NOT NULL DEFAULT 'tls',
        smtp_username VARCHAR(255) NULL,
        smtp_password_encrypted TEXT NULL,
        ses_region VARCHAR(100) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        UNIQUE KEY unique_email_settings_grotto (grotto_id),
        CONSTRAINT fk_email_settings_grotto FOREIGN KEY (grotto_id) REFERENCES grottos(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
