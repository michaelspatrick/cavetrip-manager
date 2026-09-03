<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\Http;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\AuditLogService;
use CaveTrip\Services\TripParticipantService;
use CaveTrip\Services\TripService;
use CaveTrip\Services\WaiverService;

final class TripParticipantController extends BaseController
{
    public function add(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = $this->requireMember($app);
        $grottoId = $this->grottoId($currentUser);
        $tripId = (int)($_GET['trip_id'] ?? 0);
        $trip = (new TripService($app->db()))->findForGrotto($tripId, $grottoId);

        if ($trip === null) {
            Session::flash('error', 'Trip not found.');
            return Http::redirect('/trips');
        }

        try {
            $participantId = (new TripParticipantService($app->db()))->addParticipant($trip, $_POST, null);
            (new AuditLogService($app))->participantAdded($grottoId, (int)$currentUser['id'], $tripId, $participantId);
            Session::flash('success', 'Participant added. They must complete the waiver signature before the waiver can be finalized.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to add participant: ' . $e->getMessage());
        }

        return Http::redirect('/trips/show?id=' . $tripId);
    }

    public function remove(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = $this->requireMember($app);
        $grottoId = $this->grottoId($currentUser);
        $tripId = (int)($_GET['trip_id'] ?? 0);
        $participantId = (int)($_POST['participant_id'] ?? 0);
        $trip = (new TripService($app->db()))->findForGrotto($tripId, $grottoId);

        if ($trip === null) {
            Session::flash('error', 'Trip not found.');
            return Http::redirect('/trips');
        }

        (new TripParticipantService($app->db()))->removeParticipant($participantId, $tripId);
        (new AuditLogService($app))->participantRemoved($grottoId, (int)$currentUser['id'], $tripId, $participantId);
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

        try {
            $waiver = (new WaiverService($app->db()))->renderForSignup($trip);
        } catch (\Throwable $e) {
            $waiver = null;
            Session::flash('error', $e->getMessage());
        }

        return View::render($app, 'trips/signup', [
            'title' => 'Join Trip',
            'trip' => $trip,
            'token' => $token,
            'waiver' => $waiver,
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

        $participantService = new TripParticipantService($app->db());
        $requiresWaiver = (int)($trip['waiver_template_id'] ?? 0) > 0;

        try {
            if ($requiresWaiver) {
                // Confirm the configured template is still active before accepting a signature.
                (new WaiverService($app->db()))->renderForSignup($trip);
                if (!isset($_POST['waiver_acknowledged'])) {
                    throw new \InvalidArgumentException('Please confirm that you have read and agree to the waiver.');
                }
                $signatureData = (string)($_POST['signature_data'] ?? '');
                if (!str_starts_with($signatureData, 'data:image/png;base64,')) {
                    throw new \InvalidArgumentException('Please sign the waiver before completing registration.');
                }
            }

            $app->db()->beginTransaction();
            $participantId = $participantService->addParticipant($trip, $_POST, null);
            if ($requiresWaiver) {
                $participantService->saveSignatureForParticipant(
                    $participantId,
                    (string)$_POST['signature_data'],
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                );
            }
            $app->db()->commit();

            Session::flash('success', $requiresWaiver
                ? 'Registration complete. Your trip signup and waiver signature have been saved.'
                : 'Registration complete. You are signed up for this trip.');
        } catch (\Throwable $e) {
            if ($app->db()->inTransaction()) {
                $app->db()->rollBack();
            }
            Session::flash('error', 'Unable to complete registration: ' . $e->getMessage());
        }

        return Http::redirect('/trip/signup?token=' . urlencode($token));
    }
}
