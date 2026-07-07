<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\Http;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\AuthService;

final class AuthController
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

        if ($auth->attempt($email, $password) === null) {
            Session::flash('error', 'Invalid email or password.');
            return Http::redirect('/login');
        }

        return Http::redirect('/dashboard');
    }

    public function logout(Application $app): string
    {
        Http::requirePostCsrf();
        (new AuthService($app->db()))->logout();
        return Http::redirect('/login');
    }
}
