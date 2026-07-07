<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\Http;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\AuditLogService;
use CaveTrip\Services\AuthService;
use CaveTrip\Services\WaiverTemplateService;

final class WaiverTemplateController
{
    public function index(Application $app): string
    {
        $currentUser = $this->requireAdmin($app);
        $grottoId = $this->grottoId($currentUser);

        return View::render($app, 'waiver-templates/index', [
            'title' => 'Waiver Templates',
            'currentUser' => $currentUser,
            'templates' => (new WaiverTemplateService($app->db()))->listForGrotto($grottoId),
        ]);
    }

    public function create(Application $app): string
    {
        $currentUser = $this->requireAdmin($app);
        return $this->form($app, $currentUser, null, '/waiver-templates', 'Create Waiver Template');
    }

    public function store(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = $this->requireAdmin($app);
        $grottoId = $this->grottoId($currentUser);

        try {
            $id = (new WaiverTemplateService($app->db()))->create($grottoId, $_POST);
            (new AuditLogService($app->db()))->record($grottoId, (int)$currentUser['id'], 'created', 'waiver_template', $id);
            Session::flash('success', 'Waiver template created.');
            return Http::redirect('/waiver-templates/edit?id=' . $id);
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to create waiver template: ' . $e->getMessage());
            return Http::redirect('/waiver-templates/create');
        }
    }

    public function edit(Application $app): string
    {
        $currentUser = $this->requireAdmin($app);
        $grottoId = $this->grottoId($currentUser);
        $id = (int)($_GET['id'] ?? 0);
        $template = (new WaiverTemplateService($app->db()))->findForGrotto($id, $grottoId);

        if ($template === null) {
            http_response_code(404);
            return View::render($app, 'pages/404', ['title' => 'Waiver Template Not Found']);
        }

        return $this->form($app, $currentUser, $template, '/waiver-templates/update?id=' . $id, 'Edit Waiver Template');
    }

    public function update(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = $this->requireAdmin($app);
        $grottoId = $this->grottoId($currentUser);
        $id = (int)($_GET['id'] ?? 0);

        try {
            (new WaiverTemplateService($app->db()))->update($id, $grottoId, $_POST);
            (new AuditLogService($app->db()))->record($grottoId, (int)$currentUser['id'], 'updated', 'waiver_template', $id);
            Session::flash('success', 'Waiver template updated.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to update waiver template: ' . $e->getMessage());
        }

        return Http::redirect('/waiver-templates/edit?id=' . $id);
    }

    /** @param array<string, mixed> $currentUser @param array<string, mixed>|null $template */
    private function form(Application $app, array $currentUser, ?array $template, string $action, string $title): string
    {
        return View::render($app, 'waiver-templates/form', [
            'title' => $title,
            'currentUser' => $currentUser,
            'template' => $template,
            'action' => $action,
            'placeholders' => $this->placeholders(),
        ]);
    }

    /** @return array<int, string> */
    private function placeholders(): array
    {
        return [
            '{{GROTTO_LOGO}}',
            '{{GROTTO_NAME}}',
            '{{GROTTO_EMAIL}}',
            '{{GROTTO_PHONE}}',
            '{{GROTTO_WEBSITE}}',
            '{{LANDOWNER_NAME}}',
            '{{LANDOWNER_EMAIL}}',
            '{{LANDOWNER_PHONE}}',
            '{{CAVE_NAME}}',
            '{{CAVE_DESCRIPTION}}',
            '{{PROPERTY_LOCATION}}',
            '{{TRIP_TITLE}}',
            '{{TRIP_DATE}}',
            '{{FINALIZED_DATE}}',
            '{{PARTICIPANT_LIST}}',
            '{{SIGNATURE_BLOCKS}}',
        ];
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
