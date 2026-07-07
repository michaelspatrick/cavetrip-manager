<?php

declare(strict_types=1);

$version = require $app->rootPath('config/version.php');

return json_encode([
    'app' => $version['name'],
    'version' => $version['version'],
    'build' => $version['build'],
    'release_name' => $version['release_name'],
    'status' => http_response_code() === 500 ? 'error' : 'ok',
    'database' => $dbStatus,
    'time' => date(DATE_ATOM),
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
