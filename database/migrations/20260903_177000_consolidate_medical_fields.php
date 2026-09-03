<?php

declare(strict_types=1);

return static function (PDO $db): void {
    $columnExists = static function (string $table, string $column) use ($db): bool {
        $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name');
        $stmt->execute(['table_name' => $table, 'column_name' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    };

    // Preserve any existing values by appending them to Medical / Safety Notes
    // before removing the redundant structured medical columns.
    $legacy = [
        'allergies' => 'Allergies',
        'medications' => 'Medications',
        'conditions' => 'Conditions',
        'physical_limitations' => 'Physical Limitations',
    ];

    $parts = [];
    foreach ($legacy as $column => $label) {
        if ($columnExists('trip_participants', $column)) {
            $safeLabel = str_replace("'", "''", $label);
            $parts[] = "NULLIF(CONCAT('{$safeLabel}: ', NULLIF(TRIM(`{$column}`), '')), '{$safeLabel}: ')";
        }
    }

    if ($parts !== [] && $columnExists('trip_participants', 'medical_notes')) {
        $expression = implode(', ', $parts);
        $db->exec("UPDATE trip_participants SET medical_notes = NULLIF(CONCAT_WS('\\n', NULLIF(TRIM(medical_notes), ''), {$expression}), '')");
    }

    foreach (array_keys($legacy) as $column) {
        if ($columnExists('trip_participants', $column)) {
            $db->exec("ALTER TABLE trip_participants DROP COLUMN `{$column}`");
        }
    }
};
