<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\Http;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\AuditLogService;
use CaveTrip\Services\AuthService;
use CaveTrip\Services\LandownerService;

final class LandownerController
{
    public function index(Application $app): string
    {
        $currentUser = $this->requireManager($app);
        $grottoId = $this->grottoId($currentUser);
        $landowners = (new LandownerService($app->db()))->listForGrotto($grottoId, true);

        return View::render($app, 'landowners/index', [
            'title' => 'Landowners',
            'currentUser' => $currentUser,
            'landowners' => $landowners,
        ]);
    }

    public function create(Application $app): string
    {
        $currentUser = $this->requireManager($app);

        return View::render($app, 'landowners/form', [
            'title' => 'Add Landowner',
            'currentUser' => $currentUser,
            'landowner' => null,
            'action' => '/landowners',
        ]);
    }

    public function store(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = $this->requireManager($app);
        $grottoId = $this->grottoId($currentUser);

        try {
            $id = (new LandownerService($app->db()))->create($grottoId, $_POST);
            (new AuditLogService($app->db()))->record($grottoId, (int)$currentUser['id'], 'created', 'landowner', $id);
            Session::flash('success', 'Landowner created.');
            return Http::redirect('/landowners');
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to create landowner: ' . $e->getMessage());
            return Http::redirect('/landowners/create');
        }
    }

    public function edit(Application $app): string
    {
        $currentUser = $this->requireManager($app);
        $grottoId = $this->grottoId($currentUser);
        $id = (int)($_GET['id'] ?? 0);
        $landowner = (new LandownerService($app->db()))->findForGrotto($id, $grottoId);

        if ($landowner === null) {
            http_response_code(404);
            return View::render($app, 'pages/404', ['title' => 'Landowner Not Found']);
        }

        return View::render($app, 'landowners/form', [
            'title' => 'Edit Landowner',
            'currentUser' => $currentUser,
            'landowner' => $landowner,
            'action' => '/landowners/update?id=' . $id,
        ]);
    }

    public function update(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = $this->requireManager($app);
        $grottoId = $this->grottoId($currentUser);
        $id = (int)($_GET['id'] ?? 0);

        try {
            (new LandownerService($app->db()))->update($id, $grottoId, $_POST);
            (new AuditLogService($app->db()))->record($grottoId, (int)$currentUser['id'], 'updated', 'landowner', $id);
            Session::flash('success', 'Landowner updated.');
            return Http::redirect('/landowners');
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to update landowner: ' . $e->getMessage());
            return Http::redirect('/landowners/edit?id=' . $id);
        }
    }

    /** @return array<string, mixed> */
    private function requireManager(Application $app): array
    {
        return (new AuthService($app->db()))->requireRole(['super_admin', 'grotto_admin', 'member']);
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
