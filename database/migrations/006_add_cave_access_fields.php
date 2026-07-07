<?php

declare(strict_types=1);

return static function (PDO $db): void {
    $columnExists = static function (PDO $db, string $table, string $column): bool {
        $stmt = $db->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column');
        $stmt->execute(['table' => $table, 'column' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    };

    if (!$columnExists($db, 'caves', 'access_directions')) {
        $db->exec('ALTER TABLE caves ADD access_directions TEXT NULL AFTER access_notes');
    }

    if (!$columnExists($db, 'caves', 'parking_notes')) {
        $db->exec('ALTER TABLE caves ADD parking_notes TEXT NULL AFTER access_directions');
    }

    if (!$columnExists($db, 'caves', 'gate_code')) {
        $db->exec('ALTER TABLE caves ADD gate_code VARCHAR(255) NULL AFTER parking_notes');
    }
};
