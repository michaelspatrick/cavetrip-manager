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
}
