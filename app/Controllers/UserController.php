<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\Http;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\AuthService;
use CaveTrip\Services\UserService;

final class UserController
{
    public function index(Application $app): string
    {
        $auth = new AuthService($app->db());
        $currentUser = $auth->requireRole(['super_admin', 'grotto_admin']);
        $grottoId = $currentUser['role'] === 'super_admin' ? null : (int)$currentUser['grotto_id'];
        $users = (new UserService($app->db()))->listForGrotto($grottoId);

        return View::render($app, 'users/index', [
            'title' => 'Users',
            'currentUser' => $currentUser,
            'users' => $users,
        ]);
    }

    public function create(Application $app): string
    {
        $auth = new AuthService($app->db());
        $currentUser = $auth->requireRole(['super_admin', 'grotto_admin']);

        return View::render($app, 'users/create', [
            'title' => 'Create User',
            'currentUser' => $currentUser,
        ]);
    }

    public function store(Application $app): string
    {
        Http::requirePostCsrf();
        $auth = new AuthService($app->db());
        $currentUser = $auth->requireRole(['super_admin', 'grotto_admin']);

        $role = (string)($_POST['role'] ?? 'guest');
        $allowedRoles = $currentUser['role'] === 'super_admin'
            ? ['super_admin', 'grotto_admin', 'member', 'guest']
            : ['member', 'guest'];

        if (!in_array($role, $allowedRoles, true)) {
            Session::flash('error', 'You cannot create that role.');
            return Http::redirect('/users/create');
        }

        $grottoId = $currentUser['role'] === 'super_admin'
            ? (isset($_POST['grotto_id']) && $_POST['grotto_id'] !== '' ? (int)$_POST['grotto_id'] : null)
            : (int)$currentUser['grotto_id'];

        try {
            (new UserService($app->db()))->createUser(
                $grottoId,
                $role,
                (string)($_POST['name'] ?? ''),
                (string)($_POST['email'] ?? ''),
                ($_POST['phone'] ?? '') !== '' ? (string)$_POST['phone'] : null,
                ($_POST['password'] ?? '') !== '' ? (string)$_POST['password'] : null
            );
            Session::flash('success', 'User created.');
            return Http::redirect('/users');
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to create user: ' . $e->getMessage());
            return Http::redirect('/users/create');
        }
    }
}
