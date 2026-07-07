<?php
use CaveTrip\Core\Csrf;
use CaveTrip\Core\View;
$activeCount = (int)($trip['registered_count'] ?? 0);
$max = $trip['max_attendees'] === null ? null : (int)$trip['max_attendees'];
?>
<div class="signup-shell">
    <section class="panel signup-card">
        <p class="eyebrow">Trip Signup</p>
        <h1><?= View::e($trip['title']) ?></h1>
        <p class="muted"><?= View::e($trip['trip_date']) ?><?= $trip['meeting_time'] ? ' at ' . View::e(substr((string)$trip['meeting_time'], 0, 5)) : '' ?></p>
        <div class="signup-summary">
            <div><span>Cave</span><strong><?= View::e($trip['cave_name'] ?? 'Trip destination') ?></strong></div>
            <div><span>Leader</span><strong><?= View::e($trip['leader_name'] ?? 'Trip leader') ?></strong></div>
            <div><span>Roster</span><strong><?= $activeCount ?><?= $max ? ' / ' . $max : '' ?></strong></div>
        </div>
        <?php if (!empty($trip['meeting_location'])): ?>
            <p><strong>Meeting:</strong> <?= View::e($trip['meeting_location']) ?></p>
        <?php endif; ?>
        <p class="muted">Your emergency and medical information is intended for trip leadership and emergency response use.</p>
    </section>

    <section class="panel signup-card mt">
        <h2>Your Information</h2>
        <form method="post" action="/trip/signup?token=<?= View::e($token) ?>" class="form-grid">
            <?= Csrf::field() ?>
            <?php require __DIR__ . '/participant-fields.php'; ?>
            <div class="form-actions full-width"><button class="button" type="submit">Join Trip</button></div>
        </form>
    </section>
</div>
