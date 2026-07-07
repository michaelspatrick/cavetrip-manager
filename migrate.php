<?php

declare(strict_types=1);

use CaveTrip\Services\MigrationService;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'This script must be run from the command line.' . PHP_EOL;
    exit(1);
}

$app = require __DIR__ . '/bootstrap/app.php';

try {
    $service = new MigrationService($app);
    $ran = $service->migrate();

    if ($ran === []) {
        echo "No new migrations. Database is up to date." . PHP_EOL;
        exit(0);
    }

    echo "Applied migrations:" . PHP_EOL;
    foreach ($ran as $migration) {
        echo " - {$migration}" . PHP_EOL;
    }
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
