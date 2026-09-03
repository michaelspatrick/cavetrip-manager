<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\View;
use CaveTrip\Services\DashboardStatsService;
use CaveTrip\Services\TripReportService;

final class DashboardController extends BaseController
{
    public function index(Application $app): string
    {
        $currentUser = $this->requireLogin($app);
        $role = (string)($currentUser['role'] ?? 'guest');
        $isAdmin = in_array($role, ['super_admin', 'admin'], true);
        $grottoId = (int)($currentUser['grotto_id'] ?? 0);

        $dashboard = new DashboardStatsService($app->db());
        $pendingReports = [];
        if ($role !== 'guest' && $grottoId > 0) {
            $pendingReports = (new TripReportService($app->db()))->completedTripsMissingReport(
                $grottoId,
                $this->userId($currentUser),
                $isAdmin
            );
        }

        return View::render($app, 'dashboard/index', [
            'title' => 'Dashboard',
            'currentUser' => $currentUser,
            'stats' => $dashboard->countsForUser($currentUser),
            'upcomingTrips' => $dashboard->upcomingTrips($currentUser, 5),
            'pendingReports' => $pendingReports,
        ]);
    }
}
