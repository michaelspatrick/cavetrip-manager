<?php
use CaveTrip\Core\Csrf;
use CaveTrip\Core\View;
$participants = $participants ?? [];
$latestWaiver = $latestWaiver ?? null;
$tripReports = $tripReports ?? [];
$canManageTrip = (bool)($canManageTrip ?? false);
$canReport = (bool)($canReport ?? false);
$canComplete = (bool)($canComplete ?? false);
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
<?php if($canManageTrip): ?><a class="button" href="/trips/edit?id=<?= (int)$trip['id'] ?>">Edit Trip</a><?php endif; ?>
    </div>
</div>

<div class="dashboard-grid">
    <div class="panel metric-card">
        <span class="metric-label">Roster</span>
        <strong><?= $activeCount ?><?= $max ? ' / ' . $max : '' ?></strong>
        <?php if ($max): ?>
            <div class="ctm-progress" role="progressbar" aria-label="Trip roster capacity" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $percent ?>">
                <div class="ctm-progress-bar" style="width: <?= $percent ?>%"></div>
            </div>
            <span class="progress-caption"><?= $percent ?>% of capacity</span>
        <?php else: ?>
            <span class="progress-caption">No maximum capacity set</span>
        <?php endif; ?>
    </div>
    <?php
    $signedCount = (int)($trip['signed_count'] ?? 0);
    $signedPercent = $activeCount > 0 ? min(100, (int)round(($signedCount / $activeCount) * 100)) : 0;
    ?>
    <div class="panel metric-card">
        <span class="metric-label">Waivers Signed</span>
        <strong><?= $signedCount ?> / <?= $activeCount ?></strong>
        <div class="ctm-progress" role="progressbar" aria-label="Waivers signed" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $signedPercent ?>">
            <div class="ctm-progress-bar" style="width: <?= $signedPercent ?>%"></div>
        </div>
        <span class="progress-caption"><?= $signedPercent ?>% signed</span>
        <p class="muted">Participants can sign by link or on the leader’s device.</p>
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

    <?php if($canManageTrip): ?><section class="panel">
        <h2>Share Signup Link</h2>
        <p class="muted">Share this with members or invited guests. Guests can sign up without seeing sensitive cave/location fields.</p>
        <input class="copy-field" value="<?= View::e($shareUrl) ?>" readonly onclick="this.select()">
        <p><a href="<?= View::e('/trip/signup?token=' . (string)$trip['share_token']) ?>" target="_blank">Open signup page</a></p>
    </section><?php endif; ?>
</div>

<?php if($canManageTrip): ?><section class="panel mt">
    <div class="section-header">
        <div>
            <h2>Waiver Finalization</h2>
            <p class="muted">Finalize after the active roster is complete and all active participants have signed.</p>
        </div>
        <?php if ($latestWaiver): ?>
            <a class="button secondary" target="_blank" href="/waivers/view?token=<?= View::e((string)$latestWaiver['public_token']) ?>">View Final Waiver</a>
        <?php endif; ?>
    </div>
    <?php if ($latestWaiver): ?>
        <div class="alert success"><strong>Finalized.</strong> This immutable waiver is preserved with the participant signatures captured at finalization.</div>
    <?php elseif (empty($trip['waiver_template_id'])): ?>
        <div class="alert error">No waiver template is selected for this trip. Edit the trip and choose a template first.</div>
    <?php else: ?>
        <form method="post" action="/trips/waiver/finalize?trip_id=<?= (int)$trip['id'] ?>" class="inline-form">
            <?= Csrf::field() ?><button class="button" type="submit">Finalize Waiver</button>
        </form>
    <?php endif; ?>
</section><?php endif; ?>

<?php if($canManageTrip): ?><section class="panel mt">
    <div class="section-header">
        <div>
            <h2>Participants</h2>
            <p class="muted">Trip leaders can add latecomers here and remove participants from the active roster.</p>
        </div>
        <span class="badge"><?= count($participants) ?> records</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Signature</th><th>Emergency Contact</th><th>Medical Notes</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($participants as $participant): ?>
                <tr>
                    <td><strong><?= View::e($participant['name']) ?></strong><?php if ((int)$participant['is_minor'] === 1): ?><br><span class="badge">Minor</span><?php endif; ?></td>
                    <td><?= View::e($participant['email']) ?><br><span class="muted"><?= View::e($participant['phone'] ?? '') ?></span></td>
                    <td><span class="badge badge-status"><?= View::e($participant['participant_status']) ?></span></td>
                    <td>
                        <?php if (!empty($participant['signed_at'])): ?>
                            <span class="badge success-badge">Signed</span><br><span class="muted"><?= View::e((string)$participant['signed_at']) ?></span>
                        <?php else: ?>
                            <a href="/sign?token=<?= View::e((string)($participant['signature_token'] ?? '')) ?>" target="_blank">Open sign link</a>
                        <?php endif; ?>
                    </td>
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
            <?php if ($participants === []): ?><tr><td colspan="7" class="muted">No participants yet.</td></tr><?php endif; ?>
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
</section><?php endif; ?>

<section class="panel mt">
    <div class="section-header"><div><h2>Trip Reports</h2><p class="muted">Each participant may submit a separate report after the trip is completed.</p></div><?php if((string)$trip['status']==='completed' && $canReport): ?><a class="button" href="/trip-reports/create?trip_id=<?= (int)$trip['id'] ?>">Create Trip Report</a><?php endif; ?></div>
    <?php if($tripReports): ?><div class="table-wrap"><table><thead><tr><th>Author</th><th>Submitted</th><th></th></tr></thead><tbody><?php foreach($tripReports as $report): ?><tr><td><?= View::e((string)$report['author_name']) ?></td><td><?= View::e((string)$report['submitted_at']) ?></td><td><a href="/trip-reports/show?id=<?= (int)$report['id'] ?>">View Report</a></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="muted"><?= (string)$trip['status']==='completed' ? 'No trip reports have been submitted yet.' : 'Trip reports become available after the trip is completed.' ?></p><?php endif; ?>
</section>

<?php if((string)$trip['status']!=='completed' && (string)$trip['status']!=='cancelled' && $canComplete): ?><section class="panel mt"><h2>Trip Safety Status</h2><p>When everyone is safely out, record <strong>All Out Safe</strong>. This completes the trip and opens trip reporting.</p><form method="post" action="/trips/complete?id=<?= (int)$trip['id'] ?>" class="inline-form"><?= Csrf::field() ?><button class="button" type="submit">All Out Safe — Complete Trip</button></form></section><?php endif; ?>

<?php if($canManageTrip): ?><section class="panel danger-zone mt">
    <h2>Cancel Trip</h2>
    <form method="post" action="/trips/cancel?id=<?= (int)$trip['id'] ?>" class="form-stack">
        <?= Csrf::field() ?>
        <label>Cancellation reason<textarea name="cancellation_reason" rows="3"></textarea></label>
        <div class="form-actions"><button class="button danger" type="submit">Cancel Trip</button></div>
    </form>
</section><?php endif; ?>
