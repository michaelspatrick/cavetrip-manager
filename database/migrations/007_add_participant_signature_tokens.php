<?php

declare(strict_types=1);

return static function (PDO $db): void {
    $columns = $db->query("SHOW COLUMNS FROM trip_participants LIKE 'signature_token'")->fetchAll();
    if ($columns === []) {
        $db->exec("ALTER TABLE trip_participants ADD signature_token CHAR(64) NULL UNIQUE AFTER participant_status");
    }

    $columns = $db->query("SHOW COLUMNS FROM generated_waivers LIKE 'created_at'")->fetchAll();
    if ($columns === []) {
        $db->exec("ALTER TABLE generated_waivers ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER emailed_at");
    }

    $indexes = $db->query("SHOW INDEX FROM generated_waivers WHERE Key_name = 'idx_generated_waivers_trip'")->fetchAll();
    if ($indexes === []) {
        $db->exec("CREATE INDEX idx_generated_waivers_trip ON generated_waivers (trip_id)");
    }
};
