<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\Http;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\CaveService;
use CaveTrip\Services\LandownerService;
use CaveTrip\Services\TripService;
use CaveTrip\Services\TripParticipantService;
use CaveTrip\Services\WaiverTemplateService;
use CaveTrip\Services\WaiverService;

final class TripController extends BaseController
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
            $this->audit($app)->tripCreated($grottoId, $this->userId($currentUser), $id);
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

        $participantService = new TripParticipantService($app->db());
        $participantService->ensureSignatureTokensForTrip((int)$trip['id']);
        $participants = $participantService->listForTrip((int)$trip['id']);
        $latestWaiver = (new WaiverService($app->db()))->latestForTrip((int)$trip['id']);

        return View::render($app, 'trips/show', [
            'title' => 'Manage Trip',
            'currentUser' => $currentUser,
            'trip' => $trip,
            'participants' => $participants,
            'latestWaiver' => $latestWaiver,
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
            $this->audit($app)->tripUpdated($grottoId, $this->userId($currentUser), $id);
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
            $this->audit($app)->tripCancelled($grottoId, $this->userId($currentUser), $id, $reason);
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
}
