<?php
use CaveTrip\Core\Csrf;
use CaveTrip\Core\View;
$activeCount = (int)($trip['registered_count'] ?? 0);
$max = $trip['max_attendees'] === null ? null : (int)$trip['max_attendees'];
?>
<div class="signup-shell">
    <section class="panel signup-card trip-signup-hero">
        <p class="eyebrow">Trip Signup</p>
        <h1><?= View::e($trip['title']) ?></h1>
        <p class="muted trip-date-line"><?= View::e($trip['trip_date']) ?><?= $trip['meeting_time'] ? ' at ' . View::e(substr((string)$trip['meeting_time'], 0, 5)) : '' ?></p>

        <dl class="signup-summary" aria-label="Trip details">
            <div class="signup-summary-item">
                <dt>Cave</dt>
                <dd><?= View::e($trip['cave_name'] ?? 'Trip destination') ?></dd>
            </div>
            <div class="signup-summary-item">
                <dt>Leader</dt>
                <dd><?= View::e($trip['leader_name'] ?? 'Trip leader') ?></dd>
            </div>
            <div class="signup-summary-item">
                <dt>Roster</dt>
                <dd><?= $activeCount ?><?= $max ? ' / ' . $max : '' ?></dd>
            </div>
        </dl>

        <?php if (!empty($trip['meeting_location'])): ?>
            <div class="trip-instructions">
                <span class="trip-instructions-label">Meeting / Participant Instructions</span>
                <p><?= nl2br(View::e((string)$trip['meeting_location'])) ?></p>
            </div>
        <?php endif; ?>

        <p class="privacy-note">Your emergency and medical information is intended for trip leadership and emergency response use.</p>
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
