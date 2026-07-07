<?php

declare(strict_types=1);

use CaveTrip\Core\Application;

$rootPath = dirname(__DIR__);

require_once $rootPath . '/bootstrap/env.php';
require_once $rootPath . '/app/Helpers/functions.php';

$autoload = $rootPath . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    spl_autoload_register(static function (string $class) use ($rootPath): void {
        $prefix = 'CaveTrip\\';
        if (str_starts_with($class, $prefix) === false) {
            return;
        }
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = $rootPath . '/app/' . $relative . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    });
}

loadEnv($rootPath . '/.env');

return new Application($rootPath);
