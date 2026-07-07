<?php

declare(strict_types=1);

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$router = require $app->rootPath('routes/web.php');
$router->dispatch($app);
