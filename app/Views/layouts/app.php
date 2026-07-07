<?php
use CaveTrip\Core\Csrf;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\AuthService;

$title = $title ?? 'CaveTrip Manager';
$currentUserForLayout = null;
try {
    if (isset($app)) {
        $currentUserForLayout = (new AuthService($app->db()))->user();
    }
} catch (Throwable $e) {
    $currentUserForLayout = null;
}
$success = Session::flash('success');
$error = Session::flash('error');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title) ?></title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <div class="brand">
            <strong>CaveTrip Manager</strong>
            <span class="tagline">Plan. Explore. Return Safely.</span>
        </div>
        <nav>
            <a href="/">Home</a>
            <?php if ($currentUserForLayout): ?>
                <a href="/dashboard">Dashboard</a>
                <a href="/trips">Trips</a>
                <?php if (in_array($currentUserForLayout['role'], ['super_admin', 'grotto_admin'], true)): ?>
                    <a href="/admin/grotto/settings">Grotto Settings</a>
                    <a href="/users">Users</a>
                    <a href="/waiver-templates">Waivers</a>
                    <a href="/landowners">Landowners</a>
                    <a href="/caves">Caves</a>
                <?php endif; ?>
                <form method="post" action="/logout" class="inline-form">
                    <?= Csrf::field() ?>
                    <button type="submit" class="link-button">Logout</button>
                </form>
            <?php else: ?>
                <a href="/login">Login</a>
            <?php endif; ?>
            <a href="/health">Health</a>
        </nav>
    </div>
</header>
<main class="container main-content">
    <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <?= $content ?>
</main>
<footer class="site-footer">
    <div class="container">Open-source cave trip planning, waiver, landowner, and safety management software.</div>
</footer>
</body>
</html>
