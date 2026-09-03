<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\Http;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\UserService;

final class UserController extends BaseController
{
    public function index(Application $app): string
    {
        $currentUser = $this->requireAdmin($app);
        $scope = (string)$currentUser['role'] === 'super_admin' ? null : $this->grottoId($currentUser);

        return View::render($app, 'users/index', [
            'title' => 'Users',
            'currentUser' => $currentUser,
            'users' => (new UserService($app->db()))->listForGrotto($scope),
        ]);
    }

    public function create(Application $app): string
    {
        $currentUser = $this->requireAdmin($app);
        return View::render($app, 'users/form', [
            'title' => 'Create User',
            'currentUser' => $currentUser,
            'user' => null,
            'action' => '/users',
        ]);
    }

    public function store(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = $this->requireAdmin($app);
        $role = $this->allowedRole($currentUser, (string)($_POST['role'] ?? 'guest'));
        $grottoId = (string)$currentUser['role'] === 'super_admin'
            ? (($_POST['grotto_id'] ?? '') !== '' ? (int)$_POST['grotto_id'] : null)
            : $this->grottoId($currentUser);

        try {
            $id = (new UserService($app->db()))->createUser(
                $grottoId,
                $role,
                (string)($_POST['name'] ?? ''),
                (string)($_POST['email'] ?? ''),
                ($_POST['phone'] ?? '') !== '' ? (string)$_POST['phone'] : null,
                ($_POST['password'] ?? '') !== '' ? (string)$_POST['password'] : null
            );
            Session::flash('success', 'User created.');
            return Http::redirect('/users/edit?id=' . $id);
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to create user: ' . $e->getMessage());
            return Http::redirect('/users/create');
        }
    }

    public function edit(Application $app): string
    {
        $currentUser = $this->requireAdmin($app);
        $scope = (string)$currentUser['role'] === 'super_admin' ? null : $this->grottoId($currentUser);
        $id = (int)($_GET['id'] ?? 0);
        $user = (new UserService($app->db()))->find($id, $scope);
        if ($user === null) {
            http_response_code(404);
            return View::render($app, 'pages/404', ['title' => 'User Not Found']);
        }

        return View::render($app, 'users/form', [
            'title' => 'Edit User',
            'currentUser' => $currentUser,
            'user' => $user,
            'action' => '/users/update?id=' . $id,
        ]);
    }

    public function update(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = $this->requireAdmin($app);
        $scope = (string)$currentUser['role'] === 'super_admin' ? null : $this->grottoId($currentUser);
        $id = (int)($_GET['id'] ?? 0);
        $_POST['role'] = $this->allowedRole($currentUser, (string)($_POST['role'] ?? 'guest'));

        try {
            (new UserService($app->db()))->updateUser($id, $scope, $_POST);
            $auditGrottoId = $scope ?? (int)($currentUser['grotto_id'] ?? 0);
            if ($auditGrottoId > 0) { $this->audit($app)->userUpdated($auditGrottoId, $this->userId($currentUser), $id, ['role' => (string)$_POST['role'], 'active' => isset($_POST['active'])]); }
            Session::flash('success', 'User updated.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to update user: ' . $e->getMessage());
        }
        return Http::redirect('/users/edit?id=' . $id);
    }

    /** @param array<string, mixed> $currentUser */
    private function allowedRole(array $currentUser, string $requested): string
    {
        $allowed = (string)$currentUser['role'] === 'super_admin'
            ? ['super_admin', 'admin', 'member', 'guest']
            : ['admin', 'member', 'guest'];
        if (!in_array($requested, $allowed, true)) {
            throw new \InvalidArgumentException('You cannot assign that role.');
        }
        return $requested;
    }
}
