<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\Http;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\GrottoService;
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
        return $this->renderForm($app, $currentUser, null, '/users', 'Create User');
    }

    public function store(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = $this->requireAdmin($app);
        $role = $this->allowedRole($currentUser, (string)($_POST['role'] ?? 'guest'));
        $grottoId = $this->requestedGrottoId($app, $currentUser, $role, $_POST['grotto_id'] ?? null);

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

        return $this->renderForm($app, $currentUser, $user, '/users/update?id=' . $id, 'Edit User');
    }

    public function update(Application $app): string
    {
        Http::requirePostCsrf();
        $currentUser = $this->requireAdmin($app);
        $scope = (string)$currentUser['role'] === 'super_admin' ? null : $this->grottoId($currentUser);
        $id = (int)($_GET['id'] ?? 0);
        $role = $this->allowedRole($currentUser, (string)($_POST['role'] ?? 'guest'));
        $_POST['role'] = $role;

        try {
            if ((string)$currentUser['role'] === 'super_admin') {
                $_POST['grotto_id'] = $this->requestedGrottoId($app, $currentUser, $role, $_POST['grotto_id'] ?? null);
            }

            (new UserService($app->db()))->updateUser(
                $id,
                $scope,
                $_POST,
                (string)$currentUser['role'] === 'super_admin'
            );

            $auditGrottoId = (int)($_POST['grotto_id'] ?? $scope ?? $currentUser['grotto_id'] ?? 0);
            if ($auditGrottoId > 0) {
                $this->audit($app)->userUpdated($auditGrottoId, $this->userId($currentUser), $id, [
                    'role' => $role,
                    'active' => isset($_POST['active']),
                ]);
            }
            Session::flash('success', 'User updated.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to update user: ' . $e->getMessage());
        }
        return Http::redirect('/users/edit?id=' . $id);
    }

    /** @param array<string,mixed> $currentUser @param array<string,mixed>|null $user */
    private function renderForm(Application $app, array $currentUser, ?array $user, string $action, string $title): string
    {
        return View::render($app, 'users/form', [
            'title' => $title,
            'currentUser' => $currentUser,
            'user' => $user,
            'action' => $action,
            'grottos' => (string)$currentUser['role'] === 'super_admin'
                ? (new GrottoService($app->db()))->listAll()
                : [],
        ]);
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

    /** @param array<string,mixed> $currentUser */
    private function requestedGrottoId(Application $app, array $currentUser, string $role, mixed $raw): ?int
    {
        if ((string)$currentUser['role'] !== 'super_admin') {
            return $this->grottoId($currentUser);
        }

        $value = trim((string)$raw);
        if ($value === '') {
            if ($role !== 'super_admin') {
                throw new \InvalidArgumentException('A grotto is required for Admin, Member, and Guest accounts.');
            }
            return null;
        }

        $grottoId = (int)$value;
        if ($grottoId <= 0 || !(new GrottoService($app->db()))->exists($grottoId)) {
            throw new \InvalidArgumentException('Please select a valid grotto.');
        }
        return $grottoId;
    }
}
