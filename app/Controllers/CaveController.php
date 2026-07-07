<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\Http;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\AuditLogService;
use CaveTrip\Services\AuthService;
use CaveTrip\Services\CaveService;
use CaveTrip\Services\LandownerService;

final class CaveController
{
    public function index(Application $app): string
    {
        $currentUser = $this->requireAdmin($app);
        $grottoId = $this->grottoId($currentUser);
        $caves = (new CaveService($app->db()))->listForGrotto($grottoId, true);

        return View::render($app, 'caves/index', [
            'title' => 'Caves',
            'currentUser' => $currentUser,
            'caves' => $caves,
        ]);
    }

    public function create(Application $app): string
    {
        $currentUser = $this->requireAdmin($app);
        $grottoId = $this->grottoId($currentUser);
        $landowners = (new LandownerService($app->db()))->listForGrotto($grottoId);

        return View::render($app, 'caves/form', [
            'title' => 'Add Cave',
            'currentUser' => $currentUser,
            'cave' => null,
            'landowners' => $landowners,
            'action' => '/caves',
        ]);
    }

    public function store(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = $this->requireAdmin($app);
        $grottoId = $this->grottoId($currentUser);

        try {
            $id = (new CaveService($app->db()))->create($grottoId, $_POST);
            (new AuditLogService($app->db()))->record($grottoId, (int)$currentUser['id'], 'created', 'cave', $id);
            Session::flash('success', 'Cave created.');
            return Http::redirect('/caves');
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to create cave: ' . $e->getMessage());
            return Http::redirect('/caves/create');
        }
    }

    public function edit(Application $app): string
    {
        $currentUser = $this->requireAdmin($app);
        $grottoId = $this->grottoId($currentUser);
        $id = (int)($_GET['id'] ?? 0);
        $cave = (new CaveService($app->db()))->findForGrotto($id, $grottoId);

        if ($cave === null) {
            http_response_code(404);
            return View::render($app, 'pages/404', ['title' => 'Cave Not Found']);
        }

        $landowners = (new LandownerService($app->db()))->listForGrotto($grottoId);

        return View::render($app, 'caves/form', [
            'title' => 'Edit Cave',
            'currentUser' => $currentUser,
            'cave' => $cave,
            'landowners' => $landowners,
            'action' => '/caves/update?id=' . $id,
        ]);
    }

    public function update(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = $this->requireAdmin($app);
        $grottoId = $this->grottoId($currentUser);
        $id = (int)($_GET['id'] ?? 0);

        try {
            (new CaveService($app->db()))->update($id, $grottoId, $_POST);
            (new AuditLogService($app->db()))->record($grottoId, (int)$currentUser['id'], 'updated', 'cave', $id);
            Session::flash('success', 'Cave updated.');
            return Http::redirect('/caves');
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to update cave: ' . $e->getMessage());
            return Http::redirect('/caves/edit?id=' . $id);
        }
    }

    /** @return array<string, mixed> */
    private function requireAdmin(Application $app): array
    {
        return (new AuthService($app->db()))->requireRole(['super_admin', 'grotto_admin']);
    }

    /** @param array<string, mixed> $user */
    private function grottoId(array $user): int
    {
        $grottoId = (int)($user['grotto_id'] ?? 0);
        if ($grottoId <= 0) {
            throw new \RuntimeException('A grotto-scoped account is required.');
        }
        return $grottoId;
    }
}
