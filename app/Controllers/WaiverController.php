<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\Http;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\AuditLogService;
use CaveTrip\Services\AuthService;
use CaveTrip\Services\TripParticipantService;
use CaveTrip\Services\TripService;
use CaveTrip\Services\WaiverService;
use CaveTrip\Services\NotificationService;

final class WaiverController
{
    public function finalize(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = (new AuthService($app->db()))->requireRole(['super_admin', 'admin', 'member']);
        $grottoId = (int)$currentUser['grotto_id'];
        $tripId = (int)($_GET['trip_id'] ?? 0);

        $trip = (new TripService($app->db()))->findForGrotto($tripId, $grottoId);
        if ($trip === null) {
            Session::flash('error', 'Trip not found.');
            return Http::redirect('/trips');
        }

        try {
            $participants = (new TripParticipantService($app->db()))->listForTrip($tripId);
            $waiverId = (new WaiverService($app->db()))->finalize($trip, $participants, (int)$currentUser['id']);
            (new AuditLogService($app))->waiverFinalized($grottoId,(int)$currentUser['id'],$waiverId,$tripId);
            $final=(new WaiverService($app->db()))->latestForTrip($tripId);
            if($final){$notify=new NotificationService($app);$sent=[];foreach($participants as $p){if(in_array((string)($p['participant_status']??''),['registered','signed'],true)){ $email=strtolower(trim((string)($p['email']??''))); if($email!==''&&!isset($sent[$email])){$notify->finalWaiver($trip,$email,(string)$final['public_token']);$sent[$email]=true;}}}foreach(['landowner_email','grotto_email'] as $key){$email=strtolower(trim((string)($trip[$key]??'')));if($email!==''&&!isset($sent[$email])){$notify->finalWaiver($trip,$email,(string)$final['public_token']);$sent[$email]=true;}}}
            Session::flash('success', 'Waiver finalized. The final waiver is now available from the trip dashboard.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to finalize waiver: ' . $e->getMessage());
        }

        return Http::redirect('/trips/show?id=' . $tripId);
    }

    public function view(Application $app): string
    {
        $token = (string)($_GET['token'] ?? '');
        $waiver = (new WaiverService($app->db()))->findByToken($token);
        if ($waiver === null) {
            http_response_code(404);
            return View::render($app, 'pages/404', ['title' => 'Waiver Not Found']);
        }

        return View::render($app, 'waivers/view', [
            'title' => 'View Waiver',
            'waiver' => $waiver,
        ]);
    }
    public function pdf(Application $app): string
    {
        $token = (string)($_GET['token'] ?? '');
        $waiver = (new WaiverService($app->db()))->findByToken($token);
        if ($waiver === null || empty($waiver['pdf_data'])) {
            http_response_code(404);
            return View::render($app, 'pages/404', ['title' => 'Waiver PDF Not Found']);
        }
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string)($waiver['trip_title'] ?? 'trip-waiver')) . '-final-waiver.pdf';
        header('Content-Type: application/pdf');
        header('Content-Length: ' . strlen((string)$waiver['pdf_data']));
        header('Content-Disposition: ' . (isset($_GET['download']) ? 'attachment' : 'inline') . '; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        return (string)$waiver['pdf_data'];
    }

    public function unfinalize(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = (new AuthService($app->db()))->requireRole(['super_admin', 'admin']);
        $grottoId = (int)($currentUser['grotto_id'] ?? 0);
        $tripId = (int)($_GET['trip_id'] ?? 0);
        $trip = (new TripService($app->db()))->findForGrotto($tripId, $grottoId);
        if ($trip === null) {
            Session::flash('error', 'Trip not found.');
            return Http::redirect('/trips');
        }
        try {
            $count = (new WaiverService($app->db()))->unfinalizeForTesting($tripId);
            if ($count > 0) {
                (new AuditLogService($app))->waiverUnfinalized($grottoId, (int)$currentUser['id'], $tripId, $count);
                Session::flash('success', 'Finalized test waiver removed. Participant signatures were preserved, so you can finalize the trip again.');
            } else {
                Session::flash('error', 'This trip does not currently have a finalized waiver.');
            }
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to unfinalize waiver: ' . $e->getMessage());
        }
        return Http::redirect('/trips/show?id=' . $tripId);
    }

}
