<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\Http;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\EmailService;
use CaveTrip\Services\EmailSettingsService;
use CaveTrip\Services\GrottoService;

final class EmailSettingsController extends BaseController
{
    public function edit(Application $app): string
    {
        $user = $this->requireAdmin($app);
        $grottoId = $this->grottoId($user);
        return View::render($app, 'settings/email', [
            'title'=>'Email Settings',
            'currentUser'=>$user,
            'grotto'=>(new GrottoService($app->db()))->findForUser($user),
            'settings'=>(new EmailSettingsService($app))->findForGrotto($grottoId),
        ]);
    }

    public function update(Application $app): string
    {
        Http::requirePostCsrf();
        $user = $this->requireAdmin($app);
        try {
            (new EmailSettingsService($app))->save($this->grottoId($user), $_POST);
            Session::flash('success', 'Email settings saved.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to save email settings: ' . $e->getMessage());
        }
        return Http::redirect('/admin/email/settings');
    }

    public function test(Application $app): string
    {
        Http::requirePostCsrf();
        $user = $this->requireAdmin($app);
        $to = strtolower(trim((string)($_POST['test_email'] ?? $user['email'] ?? '')));
        try {
            (new EmailService($app))->send(
                $this->grottoId($user),
                $to,
                'CaveTrip Manager test email',
                "This is a test email from CaveTrip Manager.\n\nIf you received this message, your email configuration is working.\n"
            );
            Session::flash('success', 'Test email sent to ' . $to . '.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Test email failed: ' . $e->getMessage());
        }
        return Http::redirect('/admin/email/settings');
    }
}
