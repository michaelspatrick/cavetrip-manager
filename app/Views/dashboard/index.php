<?php /** @var array<string,mixed> $user */ ?>
<section class="page-header">
    <div>
        <h1>Dashboard</h1>
        <p>Welcome, <?= e($user['name'] ?? '') ?>. This is the administrative starting point for CaveTrip Manager.</p>
    </div>
</section>

<div class="stat-grid">
    <?php foreach (($stats ?? []) as $label => $value): ?>
        <div class="stat-card">
            <span><?= e(ucwords(str_replace('_', ' ', $label))) ?></span>
            <strong><?= e($value) ?></strong>
        </div>
    <?php endforeach; ?>
</div>

<section class="card-grid two-col">
    <article class="card">
        <h2>Next Build Areas</h2>
        <ul class="check-list">
            <li>Grotto settings and branding</li>
            <li>User and role administration</li>
            <li>Landowners and cave records</li>
            <li>Trip creation and signup workflow</li>
            <li>Waiver finalization and signature rendering</li>
        </ul>
    </article>

    <article class="card">
        <h2>Admin Shortcuts</h2>
        <div class="button-row">
            <?php if (in_array(($user['role'] ?? ''), ['super_admin', 'grotto_admin'], true)): ?>
                <a class="button" href="/admin/grotto/settings">Grotto Settings</a>
                <a class="button secondary" href="/users">Users</a>
                <a class="button secondary" href="/users/create">Create User</a>
                <a class="button secondary" href="/waiver-templates">Waiver Templates</a>
                <a class="button secondary" href="/trips">Trips</a>
            <?php else: ?>
                <span class="muted">Trip tools will appear here for members in the next release.</span>
            <?php endif; ?>
        </div>
    </article>
</section>
