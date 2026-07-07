<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\View;
use CaveTrip\Services\AuthService;
use CaveTrip\Services\DashboardService;

final class DashboardController
{
    public function index(Application $app): string
    {
        $auth = new AuthService($app->db());
        $user = $auth->requireLogin();
        $stats = (new DashboardService($app->db()))->statsForUser($user);

        return View::render($app, 'dashboard/index', [
            'title' => 'Dashboard',
            'user' => $user,
            'stats' => $stats,
        ]);
    }
}
