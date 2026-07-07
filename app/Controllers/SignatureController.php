<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\Http;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\TripParticipantService;

final class SignatureController
{
    public function sign(Application $app): string
    {
        $token = (string)($_GET['token'] ?? '');
        $participant = (new TripParticipantService($app->db()))->findBySignatureToken($token);
        if ($participant === null) {
            http_response_code(404);
            return View::render($app, 'pages/404', ['title' => 'Signature Link Not Found']);
        }

        return View::render($app, 'signatures/sign', [
            'title' => 'Sign Waiver',
            'participant' => $participant,
            'token' => $token,
        ]);
    }

    public function store(Application $app): string
    {
        Http::requirePostCsrf();
        $token = (string)($_GET['token'] ?? '');
        $signatureData = (string)($_POST['signature_data'] ?? '');

        try {
            (new TripParticipantService($app->db()))->saveSignature($token, $signatureData, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);
            Session::flash('success', 'Signature saved. Thank you.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to save signature: ' . $e->getMessage());
        }

        return Http::redirect('/sign?token=' . urlencode($token));
    }
}
