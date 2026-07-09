<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\View;
use CaveTrip\Services\AuditLogService;

final class AuditLogController
{
    public function index(Application $app): string
    {
        $auditLogService = new AuditLogService($app);

        return View::render($app, 'audit-logs/index', [
            'title' => 'Audit Log',
            'logs' => $auditLogService->latest(100),
        ]);
    }
}
