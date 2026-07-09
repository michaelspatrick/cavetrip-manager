<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Services\AuditLogService;
use CaveTrip\Services\AuthService;

abstract class BaseController
{
    protected function auth(Application $app): AuthService
    {
        return new AuthService($app->db());
    }

    protected function audit(Application $app): AuditLogService
    {
        return new AuditLogService($app);
    }

    /** @return array<string, mixed> */
    protected function requireMember(Application $app): array
    {
        return $this->auth($app)->requireRole(['super_admin', 'grotto_admin', 'member']);
    }

    /** @return array<string, mixed> */
    protected function requireAdmin(Application $app): array
    {
        return $this->auth($app)->requireRole(['super_admin', 'grotto_admin']);
    }

    /** @param array<string, mixed> $user */
    protected function grottoId(array $user): int
    {
        $grottoId = (int)($user['grotto_id'] ?? 0);

        if ($grottoId <= 0) {
            throw new \RuntimeException('A grotto-scoped account is required.');
        }

        return $grottoId;
    }

    /** @param array<string, mixed> $user */
    protected function userId(array $user): int
    {
        return (int)($user['id'] ?? 0);
    }
}
