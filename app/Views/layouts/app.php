<?php

use CaveTrip\Core\Csrf;

$version = require $app->rootPath('config/version.php');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$isActive = static function (string $path) use ($currentPath): string {
    return str_starts_with($currentPath, $path) ? 'active' : '';
};
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? $version['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css?v=0.14.1">
</head>
<body>
<div class="ctm-shell">
    <aside class="ctm-sidebar">
        <div class="ctm-brand">
            <div class="ctm-brand-title">CaveTrip Manager</div>
            <div class="ctm-brand-tagline">Plan. Explore. Return Safely.</div>
        </div>

        <nav class="ctm-nav">
            <a class="<?= $isActive('/dashboard') ?>" href="/dashboard">Dashboard</a>
            <a class="<?= $isActive('/trips') ?>" href="/trips">Trips</a>
            <a class="<?= $isActive('/waiver-templates') ?>" href="/waiver-templates">Waiver Templates</a>
            <a class="<?= $isActive('/landowners') ?>" href="/landowners">Landowners</a>
            <a class="<?= $isActive('/caves') ?>" href="/caves">Caves</a>
            <a class="<?= $isActive('/users') ?>" href="/users">Users</a>
            <a class="<?= $isActive('/audit-logs') ?>" href="/audit-logs">Audit Log</a>
            <a class="<?= $isActive('/admin/grotto/settings') ?>" href="/admin/grotto/settings">Grotto Settings</a>
            <a class="<?= $isActive('/about') ?>" href="/about">About</a>
            <a href="/health">Health</a>
        </nav>
    </aside>

    <div class="ctm-main">
        <header class="ctm-topbar">
            <div class="ctm-page-title"><?= htmlspecialchars($title ?? 'Dashboard') ?></div>

            <form method="post" action="/logout" class="m-0">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-outline-secondary btn-sm">Logout</button>
            </form>
        </header>

        <main class="ctm-content">
            <?= $content ?? '' ?>
        </main>

        <footer class="ctm-footer">
            <?= htmlspecialchars($version['name']) ?>
            v<?= htmlspecialchars($version['version']) ?>
            · Build <?= htmlspecialchars($version['build']) ?>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
