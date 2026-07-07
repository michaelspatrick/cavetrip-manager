<?php
use CaveTrip\Core\Csrf;
use CaveTrip\Core\View;
$participants = $participants ?? [];
$shareUrl = app_url('/trip/signup?token=' . (string)($trip['share_token'] ?? ''));
$activeCount = (int)($trip['registered_count'] ?? 0);
$max = $trip['max_attendees'] === null ? null : (int)$trip['max_attendees'];
$percent = $max ? min(100, (int)round(($activeCount / $max) * 100)) : 0;
?>
<div class="page-header">
    <div>
        <p class="eyebrow">Trip Dashboard</p>
        <h1><?= View::e($trip['title']) ?></h1>
        <p><?= View::e($trip['trip_number']) ?> · <?= View::e($trip['trip_date']) ?> · <span class="badge badge-status"><?= View::e($trip['status']) ?></span></p>
    </div>
    <div class="button-row">
        <a class="button secondary" href="/trips">All Trips</a>
        <a class="button" href="/trips/edit?id=<?= (int)$trip['id'] ?>">Edit Trip</a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="panel metric-card">
        <span class="metric-label">Roster</span>
        <strong><?= $activeCount ?><?= $max ? ' / ' . $max : '' ?></strong>
        <div class="progress"><span style="width: <?= $max ? $percent : 0 ?>%"></span></div>
    </div>
    <div class="panel metric-card">
        <span class="metric-label">Waivers Signed</span>
        <strong><?= (int)($trip['signed_count'] ?? 0) ?></strong>
        <p class="muted">Signature capture comes in the waiver release.</p>
    </div>
    <div class="panel metric-card">
        <span class="metric-label">Callout</span>
        <strong><?= $trip['callout_time'] ? View::e(substr((string)$trip['callout_time'], 0, 16)) : 'Not set' ?></strong>
        <p class="muted"><?= View::e((string)$trip['callout_status']) ?></p>
    </div>
</div>

<div class="grid two">
    <section class="panel">
        <h2>Trip Details</h2>
        <dl class="detail-list">
            <dt>Cave</dt><dd><?= View::e($trip['cave_name'] ?? 'Not selected') ?></dd>
            <dt>Landowner</dt><dd><?= View::e($trip['landowner_name'] ?? 'Not selected') ?></dd>
            <dt>Leader</dt><dd><?= View::e($trip['leader_name'] ?? 'Unknown') ?></dd>
            <dt>Meeting</dt><dd><?= View::e($trip['meeting_location'] ?? 'Not set') ?></dd>
            <dt>Visibility</dt><dd><?= View::e($trip['visibility']) ?></dd>
            <dt>Minimum</dt><dd><?= $trip['min_attendees'] ? (int)$trip['min_attendees'] : 'None' ?></dd>
            <dt>Maximum</dt><dd><?= $trip['max_attendees'] ? (int)$trip['max_attendees'] : 'None' ?></dd>
        </dl>
    </section>

    <section class="panel">
        <h2>Share Signup Link</h2>
        <p class="muted">Share this with members or invited guests. Guests can sign up without seeing sensitive cave/location fields.</p>
        <input class="copy-field" value="<?= View::e($shareUrl) ?>" readonly onclick="this.select()">
        <p><a href="<?= View::e('/trip/signup?token=' . (string)$trip['share_token']) ?>" target="_blank">Open signup page</a></p>
    </section>
</div>

<section class="panel mt">
    <div class="section-header">
        <div>
            <h2>Participants</h2>
            <p class="muted">Trip leaders can add latecomers here and remove participants from the active roster.</p>
        </div>
        <span class="badge"><?= count($participants) ?> records</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Emergency Contact</th><th>Medical Notes</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($participants as $participant): ?>
                <tr>
                    <td><strong><?= View::e($participant['name']) ?></strong><?php if ((int)$participant['is_minor'] === 1): ?><br><span class="badge">Minor</span><?php endif; ?></td>
                    <td><?= View::e($participant['email']) ?><br><span class="muted"><?= View::e($participant['phone'] ?? '') ?></span></td>
                    <td><span class="badge badge-status"><?= View::e($participant['participant_status']) ?></span></td>
                    <td><?= View::e($participant['emergency_contact_name']) ?><br><span class="muted"><?= View::e($participant['emergency_contact_phone']) ?></span></td>
                    <td><?= $participant['medical_notes'] ? View::e(mb_strimwidth((string)$participant['medical_notes'], 0, 80, '…')) : '<span class="muted">None entered</span>' ?></td>
                    <td>
                        <?php if (!in_array($participant['participant_status'], ['removed','cancelled'], true)): ?>
                        <form class="inline-form" method="post" action="/trips/participants/remove?trip_id=<?= (int)$trip['id'] ?>">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="participant_id" value="<?= (int)$participant['id'] ?>">
                            <button class="link-button danger-link" type="submit">Remove</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($participants === []): ?><tr><td colspan="6" class="muted">No participants yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel mt">
    <h2>Add Walk-In / Late Participant</h2>
    <form method="post" action="/trips/participants/add?trip_id=<?= (int)$trip['id'] ?>" class="form-grid">
        <?= Csrf::field() ?>
        <?php require __DIR__ . '/participant-fields.php'; ?>
        <div class="form-actions full-width"><button class="button" type="submit">Add Participant</button></div>
    </form>
</section>

<section class="panel danger-zone mt">
    <h2>Cancel Trip</h2>
    <p class="muted">Cancellation emails will be wired into the notification release.</p>
    <form method="post" action="/trips/cancel?id=<?= (int)$trip['id'] ?>" class="form-stack">
        <?= Csrf::field() ?>
        <label>Cancellation reason<textarea name="cancellation_reason" rows="3"></textarea></label>
        <div class="form-actions"><button class="button danger" type="submit">Cancel Trip</button></div>
    </form>
</section>
