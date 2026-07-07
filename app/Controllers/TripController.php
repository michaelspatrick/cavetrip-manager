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
use CaveTrip\Services\TripService;
use CaveTrip\Services\TripParticipantService;
use CaveTrip\Services\WaiverTemplateService;

final class TripController
{
    public function index(Application $app): string
    {
        $currentUser = $this->requireMember($app);
        $grottoId = $this->grottoId($currentUser);
        $trips = (new TripService($app->db()))->listForGrotto($grottoId);

        return View::render($app, 'trips/index', [
            'title' => 'Trips',
            'currentUser' => $currentUser,
            'trips' => $trips,
        ]);
    }

    public function create(Application $app): string
    {
        $currentUser = $this->requireMember($app);
        return $this->form($app, $currentUser, null, '/trips', 'Create Trip');
    }

    public function store(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = $this->requireMember($app);
        $grottoId = $this->grottoId($currentUser);

        try {
            $id = (new TripService($app->db()))->create($grottoId, $_POST, $currentUser);
            (new AuditLogService($app->db()))->record($grottoId, (int)$currentUser['id'], 'created', 'trip', $id);
            Session::flash('success', 'Trip created.');
            return Http::redirect('/trips/show?id=' . $id);
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to create trip: ' . $e->getMessage());
            return Http::redirect('/trips/create');
        }
    }

    public function show(Application $app): string
    {
        $currentUser = $this->requireMember($app);
        $grottoId = $this->grottoId($currentUser);
        $id = (int)($_GET['id'] ?? 0);
        $trip = (new TripService($app->db()))->findForGrotto($id, $grottoId);

        if ($trip === null) {
            http_response_code(404);
            return View::render($app, 'pages/404', ['title' => 'Trip Not Found']);
        }

        return View::render($app, 'trips/show', [
            'title' => 'Manage Trip',
            'currentUser' => $currentUser,
            'trip' => $trip,
            'participants' => (new TripParticipantService($app->db()))->listForTrip((int)$trip['id']),
        ]);
    }

    public function edit(Application $app): string
    {
        $currentUser = $this->requireMember($app);
        $grottoId = $this->grottoId($currentUser);
        $id = (int)($_GET['id'] ?? 0);
        $trip = (new TripService($app->db()))->findForGrotto($id, $grottoId);

        if ($trip === null) {
            http_response_code(404);
            return View::render($app, 'pages/404', ['title' => 'Trip Not Found']);
        }

        return $this->form($app, $currentUser, $trip, '/trips/update?id=' . $id, 'Edit Trip');
    }

    public function update(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = $this->requireMember($app);
        $grottoId = $this->grottoId($currentUser);
        $id = (int)($_GET['id'] ?? 0);

        try {
            (new TripService($app->db()))->update($id, $grottoId, $_POST, $currentUser);
            (new AuditLogService($app->db()))->record($grottoId, (int)$currentUser['id'], 'updated', 'trip', $id);
            Session::flash('success', 'Trip updated.');
            return Http::redirect('/trips/show?id=' . $id);
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to update trip: ' . $e->getMessage());
            return Http::redirect('/trips/edit?id=' . $id);
        }
    }

    public function cancel(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = $this->requireMember($app);
        $grottoId = $this->grottoId($currentUser);
        $id = (int)($_GET['id'] ?? 0);
        $reason = (string)($_POST['cancellation_reason'] ?? '');

        try {
            (new TripService($app->db()))->cancel($id, $grottoId, (int)$currentUser['id'], $reason);
            (new AuditLogService($app->db()))->record($grottoId, (int)$currentUser['id'], 'cancelled', 'trip', $id);
            Session::flash('success', 'Trip cancelled. Participant notifications will be added in the next notification release.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to cancel trip: ' . $e->getMessage());
        }

        return Http::redirect('/trips/show?id=' . $id);
    }

    /** @param array<string, mixed> $currentUser @param array<string, mixed>|null $trip */
    private function form(Application $app, array $currentUser, ?array $trip, string $action, string $title): string
    {
        $grottoId = $this->grottoId($currentUser);
        return View::render($app, 'trips/form', [
            'title' => $title,
            'currentUser' => $currentUser,
            'trip' => $trip,
            'action' => $action,
            'caves' => (new CaveService($app->db()))->listForGrotto($grottoId),
            'landowners' => (new LandownerService($app->db()))->listForGrotto($grottoId),
            'waiverTemplates' => (new WaiverTemplateService($app->db()))->listActiveForGrotto($grottoId),
        ]);
    }

    /** @return array<string, mixed> */
    private function requireMember(Application $app): array
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
