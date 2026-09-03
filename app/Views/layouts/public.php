<?php
use CaveTrip\Core\Session;
use CaveTrip\Core\View;

$version = require $app->rootPath('config/version.php');
$appName = (string)($version['name'] ?? 'CaveTrip Manager');
$brandName = (string)($trip['grotto_name'] ?? $participant['grotto_name'] ?? 'CaveTrip Manager');
$brandLogo = (string)($trip['grotto_logo_file_path'] ?? $trip['grotto_logo_url'] ?? $participant['grotto_logo_file_path'] ?? $participant['grotto_logo_url'] ?? '');
$brandWebsite = (string)($trip['grotto_website_url'] ?? $participant['grotto_website_url'] ?? '');
$success = Session::flash('success');
$error = Session::flash('error');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= View::e($title ?? $brandName) ?></title>
    <link rel="stylesheet" href="/css/app.css?v=0.17.10">
</head>
<body class="ctm-public-body">
<div class="ctm-public-shell">
    <header class="ctm-public-header">
        <div class="ctm-public-brand">
            <?php if ($brandLogo !== ''): ?>
                <?php if ($brandWebsite !== ''): ?><a href="<?= View::e($brandWebsite) ?>" target="_blank" rel="noopener noreferrer"><?php endif; ?>
                <img src="<?= View::e($brandLogo) ?>" alt="<?= View::e($brandName) ?> logo">
                <?php if ($brandWebsite !== ''): ?></a><?php endif; ?>
            <?php endif; ?>
            <div>
                <div class="ctm-public-brand-name"><?= View::e($brandName) ?></div>
                <div class="ctm-public-brand-tagline">Trip planning and participant safety</div>
            </div>
        </div>
    </header>

    <main class="ctm-public-content">
        <?php if ($success): ?><div class="alert success"><?= View::e($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert error"><?= View::e($error) ?></div><?php endif; ?>
        <?= $content ?? '' ?>
    </main>

    <footer class="ctm-public-footer">
        <span><?= View::e($brandName) ?></span>
        <span>Powered by <?= View::e($appName) ?></span>
    </footer>
</div>
</body>
</html>
