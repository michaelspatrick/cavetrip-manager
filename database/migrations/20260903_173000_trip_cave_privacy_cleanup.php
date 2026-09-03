<?php

declare(strict_types=1);

return static function (PDO $db): void {
    $columnExists = static function (PDO $db, string $table, string $column): bool {
        $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name');
        $stmt->execute(['table_name'=>$table,'column_name'=>$column]);
        return (int)$stmt->fetchColumn() > 0;
    };

    foreach (['general_area','gps_latitude','gps_longitude'] as $column) {
        if ($columnExists($db,'caves',$column)) {
            $db->exec("ALTER TABLE caves DROP COLUMN `{$column}`");
        }
    }

    if ($columnExists($db,'trips','cave_description')) {
        $db->exec('ALTER TABLE trips DROP COLUMN cave_description');
    }
};
