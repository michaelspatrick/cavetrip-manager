<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'This script must be run from the command line.' . PHP_EOL;
    exit(1);
}

$options = getopt('', [
    'email:',
    'password:',
]);

$email = strtolower(trim((string)($options['email'] ?? '')));
$password = (string)($options['password'] ?? '');

if ($email === '' || $password === '') {
    echo 'Usage: php tools/reset_password.php --email=user@example.com --password="NewStrongPassword"' . PHP_EOL;
    exit(1);
}

if (strlen($password) < 12) {
    echo 'Error: password must be at least 12 characters.' . PHP_EOL;
    exit(1);
}

$app = require __DIR__ . '/../bootstrap/app.php';

$pdo = $app->db();

$stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Error: user not found for email {$email}" . PHP_EOL;
    exit(1);
}

$update = $pdo->prepare('
    UPDATE users
    SET password_hash = :password_hash,
        updated_at = NOW()
    WHERE id = :id
');

$update->execute([
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'id' => (int)$user['id'],
]);

echo "Password reset for {$user['name']} <{$user['email']}>." . PHP_EOL;
