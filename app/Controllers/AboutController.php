<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\View;

final class AboutController
{
    public function index(Application $app): string
    {
        $version = require $app->rootPath('config/version.php');

        return View::render($app, 'pages/about', [
            'title' => 'About CaveTrip Manager',
            'version' => $version,
        ]);
    }
}
