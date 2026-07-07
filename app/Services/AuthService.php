<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use CaveTrip\Core\Session;
use PDO;

final class AuthService
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return array<string, mixed>|null */
    public function attempt(string $email, string $password): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email AND active = 1 LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email))]);
        $user = $stmt->fetch();

        if (!$user || !is_string($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        Session::regenerate();
        Session::put('user_id', (int)$user['id']);
        return $user;
    }

    public function logout(): void
    {
        Session::destroy();
    }

    /** @return array<string, mixed>|null */
    public function user(): ?array
    {
        $userId = Session::get('user_id');
        if (!is_int($userId) && !ctype_digit((string)$userId)) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id AND active = 1 LIMIT 1');
        $stmt->execute(['id' => (int)$userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function requireLogin(): array
    {
        $user = $this->user();
        if ($user === null) {
            header('Location: /login', true, 302);
            exit;
        }
        return $user;
    }

    /** @param array<int, string> $roles */
    public function requireRole(array $roles): array
    {
        $user = $this->requireLogin();
        if (!in_array((string)$user['role'], $roles, true)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
        return $user;
    }
}
