<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\Http;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\AuthService;

final class AuthController extends BaseController
{
    public function showLogin(Application $app): string
    {
        return View::render($app, 'auth/login', [
            'title' => 'Sign In',
        ]);
    }

    public function login(Application $app): string
    {
        Http::requirePostCsrf();

        $email = (string)($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $auth = new AuthService($app->db());

        $user = $auth->attempt($email, $password);

        if ($user === null) {
            Session::flash('error', 'Invalid email or password.');
            return Http::redirect('/login');
        }

        $grottoId = (int)($user['grotto_id'] ?? 0);
        $userId = (int)($user['id'] ?? 0);

        if ($grottoId > 0 && $userId > 0) {
            $this->audit($app)->userLoggedIn($grottoId, $userId);
        }

        return Http::redirect('/dashboard');
    }

    public function logout(Application $app): string
    {
        Http::requirePostCsrf();

        $auth = new AuthService($app->db());
        $user = $auth->user();

        if (is_array($user)) {
            $grottoId = (int)($user['grotto_id'] ?? 0);
            $userId = (int)($user['id'] ?? 0);

            if ($grottoId > 0 && $userId > 0) {
                $this->audit($app)->userLoggedOut($grottoId, $userId);
            }
        }

        $auth->logout();

        return Http::redirect('/login');
    }
}
