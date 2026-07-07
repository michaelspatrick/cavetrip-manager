<?php

declare(strict_types=1);

use CaveTrip\Services\UserService;

$app = require dirname(__DIR__) . '/bootstrap/app.php';

$options = getopt('', [
    'name:',
    'email:',
    'password:',
    'grotto-name::',
    'grotto-slug::',
]);

$name = trim((string)($options['name'] ?? ''));
$email = strtolower(trim((string)($options['email'] ?? '')));
$password = (string)($options['password'] ?? '');
$grottoName = trim((string)($options['grotto-name'] ?? ''));
$grottoSlug = trim((string)($options['grotto-slug'] ?? ''));

if ($name === '' || $email === '' || $password === '') {
    fwrite(STDERR, "Usage: php tools/create_admin.php --name=\"Admin Name\" --email=admin@example.com --password=\"StrongPassword\" [--grotto-name=\"Six Ridges Grotto\" --grotto-slug=six-ridges]\n");
    exit(1);
}

$db = $app->db();
$db->beginTransaction();

try {
    $grottoId = null;
    if ($grottoName !== '') {
        $slug = $grottoSlug !== '' ? $grottoSlug : strtolower(preg_replace('/[^a-z0-9]+/i', '-', $grottoName));
        $slug = trim((string)$slug, '-');
        $stmt = $db->prepare('INSERT INTO grottos (name, slug, active) VALUES (:name, :slug, 1) ON DUPLICATE KEY UPDATE name = VALUES(name), active = 1');
        $stmt->execute(['name' => $grottoName, 'slug' => $slug]);
        $stmt = $db->prepare('SELECT id FROM grottos WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $grottoId = (int)$stmt->fetchColumn();
    }

    $role = $grottoId === null ? 'super_admin' : 'grotto_admin';
    $userId = (new UserService($db))->createUser($grottoId, $role, $name, $email, null, $password);
    $db->commit();
    echo "Created {$role} user #{$userId}: {$email}\n";
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, 'Unable to create admin: ' . $e->getMessage() . "\n");
    exit(1);
}
