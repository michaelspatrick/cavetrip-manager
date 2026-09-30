<?php

declare(strict_types=1);

return static function (PDO $db): void {
    $columns = $db->query("SHOW COLUMNS FROM generated_waivers")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('participant_snapshot_json', $columns, true)) {
        $db->exec("ALTER TABLE generated_waivers ADD COLUMN participant_snapshot_json LONGTEXT NULL AFTER final_html");
    }
    if (!in_array('pdf_data', $columns, true)) {
        $db->exec("ALTER TABLE generated_waivers ADD COLUMN pdf_data MEDIUMBLOB NULL AFTER participant_snapshot_json");
    }
    if (!in_array('pdf_sha256', $columns, true)) {
        $db->exec("ALTER TABLE generated_waivers ADD COLUMN pdf_sha256 CHAR(64) NULL AFTER pdf_data");
    }
};
