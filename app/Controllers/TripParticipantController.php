<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\Http;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\TripParticipantService;
use CaveTrip\Services\TripService;

final class TripParticipantController extends BaseController
{
    public function add(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = $this->requireMember($app);
        $grottoId = (int)$currentUser['grotto_id'];
        $tripId = (int)($_GET['trip_id'] ?? 0);
        $trip = (new TripService($app->db()))->findForGrotto($tripId, $grottoId);

        if ($trip === null) {
            Session::flash('error', 'Trip not found.');
            return Http::redirect('/trips');
        }

        try {
            $participantId = (new TripParticipantService($app->db()))->addParticipant($trip, $_POST, null);
            $this->audit($app)->participantAdded($grottoId, $this->userId($currentUser), $tripId, $participantId);
            Session::flash('success', 'Participant added.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to add participant: ' . $e->getMessage());
        }

        return Http::redirect('/trips/show?id=' . $tripId);
    }

    public function remove(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = $this->requireMember($app);
        $grottoId = (int)$currentUser['grotto_id'];
        $tripId = (int)($_GET['trip_id'] ?? 0);
        $participantId = (int)($_POST['participant_id'] ?? 0);
        $trip = (new TripService($app->db()))->findForGrotto($tripId, $grottoId);

        if ($trip === null) {
            Session::flash('error', 'Trip not found.');
            return Http::redirect('/trips');
        }

        (new TripParticipantService($app->db()))->removeParticipant($participantId, $tripId);
        $this->audit($app)->participantRemoved($grottoId, $this->userId($currentUser), $tripId, $participantId);
        Session::flash('success', 'Participant removed from active roster.');
        return Http::redirect('/trips/show?id=' . $tripId);
    }

    public function publicSignup(Application $app): string
    {
        $token = (string)($_GET['token'] ?? '');
        $trip = (new TripService($app->db()))->findByShareToken($token);
        if ($trip === null || (string)$trip['status'] === 'cancelled') {
            http_response_code(404);
            return View::render($app, 'pages/404', ['title' => 'Trip Not Found']);
        }

        return View::render($app, 'trips/signup', [
            'title' => 'Join Trip',
            'trip' => $trip,
            'token' => $token,
        ]);
    }

    public function publicSignupStore(Application $app): string
    {
        Http::requirePostCsrf();
        $token = (string)($_GET['token'] ?? '');
        $trip = (new TripService($app->db()))->findByShareToken($token);
        if ($trip === null || (string)$trip['status'] === 'cancelled') {
            http_response_code(404);
            return View::render($app, 'pages/404', ['title' => 'Trip Not Found']);
        }

        try {
            (new TripParticipantService($app->db()))->addParticipant($trip, $_POST, null);
            Session::flash('success', 'You are signed up for this trip. Watch your email for future waiver/signature updates.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to sign up: ' . $e->getMessage());
        }

        return Http::redirect('/trip/signup?token=' . urlencode($token));
    }
}
