<?php
use CaveTrip\Core\View;

$role = (string)($currentUser['role'] ?? 'guest');
$isAdmin = in_array($role, ['super_admin', 'admin'], true);
$isMember = in_array($role, ['super_admin', 'admin', 'member'], true);

$statusLabels = [
    'draft' => 'Draft',
    'open' => 'Open',
    'closed' => 'Closed',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];

$attention = [];
foreach ($upcomingTrips as $trip) {
    $registered = (int)($trip['registered_count'] ?? 0);
    $signed = (int)($trip['signed_count'] ?? 0);
    $max = (int)($trip['max_attendees'] ?? 0);
    $unsigned = max(0, $registered - $signed);

    if ($unsigned > 0) {
        $attention[] = [
            'trip_id' => (int)$trip['id'],
            'title' => (string)$trip['title'],
            'kind' => 'Waivers pending',
            'detail' => $unsigned . ' participant' . ($unsigned === 1 ? '' : 's') . ' still need' . ($unsigned === 1 ? 's' : '') . ' to sign.',
        ];
    }

    if ($max > 0 && $registered >= $max) {
        $attention[] = [
            'trip_id' => (int)$trip['id'],
            'title' => (string)$trip['title'],
            'kind' => 'Trip full',
            'detail' => $registered . ' of ' . $max . ' roster spots are filled.',
        ];
    } elseif ($max > 0 && $registered >= (int)ceil($max * .8)) {
        $attention[] = [
            'trip_id' => (int)$trip['id'],
            'title' => (string)$trip['title'],
            'kind' => 'Near capacity',
            'detail' => $registered . ' of ' . $max . ' roster spots are filled.',
        ];
    }

    $closes = trim((string)($trip['signup_closes_at'] ?? ''));
    if ($closes !== '') {
        $closeTs = strtotime($closes);
        if ($closeTs !== false && $closeTs >= time() && $closeTs <= strtotime('+7 days')) {
            $attention[] = [
                'trip_id' => (int)$trip['id'],
                'title' => (string)$trip['title'],
                'kind' => 'Signup closing soon',
                'detail' => 'Signup closes ' . date('M j, Y g:i A', $closeTs) . '.',
            ];
        }
    }
}
?>

<div class="page-header dashboard-heading">
    <div>
        <h1>Dashboard</h1>
        <p>Welcome, <?= View::e((string)($currentUser['name'] ?? '')) ?>. Here is what needs attention for your trips.</p>
    </div>
    <?php if ($isMember): ?>
        <a class="button" href="/trips/create">Create Trip</a>
    <?php endif; ?>
</div>

<div class="stat-grid dashboard-stats">
    <a class="stat-card" href="/trips">
        <span class="stat-label">Upcoming Trips</span>
        <strong><?= number_format((int)($stats['upcoming_trips'] ?? 0)) ?></strong>
    </a>
    <?php if ($isMember): ?>
        <a class="stat-card" href="/trips">
            <span class="stat-label">Participants</span>
            <strong><?= number_format((int)($stats['participants'] ?? 0)) ?></strong>
        </a>
        <a class="stat-card<?= ((int)($stats['waivers_pending'] ?? 0) > 0) ? ' stat-card-attention' : '' ?>" href="/trips">
            <span class="stat-label">Waivers Pending</span>
            <strong><?= number_format((int)($stats['waivers_pending'] ?? 0)) ?></strong>
        </a>
        <a class="stat-card<?= ((int)($stats['reports_needed'] ?? 0) > 0) ? ' stat-card-attention' : '' ?>" href="/trip-reports">
            <span class="stat-label">Reports Needed</span>
            <strong><?= number_format((int)($stats['reports_needed'] ?? 0)) ?></strong>
        </a>
        <a class="stat-card" href="/caves">
            <span class="stat-label">Caves</span>
            <strong><?= number_format((int)($stats['caves'] ?? 0)) ?></strong>
        </a>
    <?php else: ?>
        <a class="stat-card<?= ((int)($stats['waivers_pending'] ?? 0) > 0) ? ' stat-card-attention' : '' ?>" href="/trips">
            <span class="stat-label">My Waivers Pending</span>
            <strong><?= number_format((int)($stats['waivers_pending'] ?? 0)) ?></strong>
        </a>
    <?php endif; ?>
</div>

<div class="dashboard-operations-grid">
    <section class="panel dashboard-panel">
        <div class="panel-heading-row">
            <div>
                <h2>Upcoming Trips</h2>
                <p class="text-muted">The next trips on the calendar.</p>
            </div>
            <a href="/trips">View all</a>
        </div>

        <?php if ($upcomingTrips === []): ?>
            <div class="empty-state">
                <strong>No upcoming trips.</strong>
                <?php if ($isMember): ?><span>Create a trip when the next outing is ready to plan.</span><?php endif; ?>
            </div>
        <?php else: ?>
            <div class="upcoming-trip-list">
                <?php foreach ($upcomingTrips as $trip):
                    $registered = (int)($trip['registered_count'] ?? 0);
                    $signed = (int)($trip['signed_count'] ?? 0);
                    $max = max(0, (int)($trip['max_attendees'] ?? 0));
                    $rosterText = $max > 0 ? $registered . ' / ' . $max : (string)$registered;
                    $status = (string)($trip['status'] ?? 'draft');
                ?>
                    <a class="upcoming-trip-row" href="/trips/show?id=<?= (int)$trip['id'] ?>">
                        <div class="upcoming-trip-date">
                            <strong><?= View::e(date('M j', strtotime((string)$trip['trip_date']))) ?></strong>
                            <span><?= View::e(date('Y', strtotime((string)$trip['trip_date']))) ?></span>
                        </div>
                        <div class="upcoming-trip-main">
                            <div class="upcoming-trip-title-row">
                                <strong><?= View::e((string)$trip['title']) ?></strong>
                                <span class="status-badge status-<?= View::e($status) ?>"><?= View::e($statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status))) ?></span>
                            </div>
                            <span><?= View::e((string)($trip['cave_name'] ?: 'Cave not assigned')) ?><?= !empty($trip['leader_name']) ? ' · Leader: ' . View::e((string)$trip['leader_name']) : '' ?></span>
                        </div>
                        <div class="upcoming-trip-metrics">
                            <span><strong><?= View::e($rosterText) ?></strong> roster</span>
                            <span><strong><?= $signed ?> / <?= $registered ?></strong> signed</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($isMember): ?>
    <section class="panel dashboard-panel">
        <div class="panel-heading-row">
            <div>
                <h2>Needs Attention</h2>
                <p class="text-muted">Items worth checking before the next trip.</p>
            </div>
        </div>

        <?php if ($attention === [] && $pendingReports === []): ?>
            <div class="empty-state empty-state-good">
                <strong>Nothing urgent right now.</strong>
                <span>Upcoming rosters, waivers, signup windows, and reports look clear.</span>
            </div>
        <?php else: ?>
            <div class="attention-list">
                <?php foreach (array_slice($attention, 0, 6) as $item): ?>
                    <a href="/trips/show?id=<?= (int)$item['trip_id'] ?>">
                        <span class="attention-kind"><?= View::e((string)$item['kind']) ?></span>
                        <strong><?= View::e((string)$item['title']) ?></strong>
                        <small><?= View::e((string)$item['detail']) ?></small>
                    </a>
                <?php endforeach; ?>
                <?php foreach (array_slice($pendingReports, 0, max(0, 6 - count($attention))) as $trip): ?>
                    <a href="/trip-reports/create?trip_id=<?= (int)$trip['id'] ?>">
                        <span class="attention-kind">Trip report needed</span>
                        <strong><?= View::e((string)$trip['title']) ?></strong>
                        <small><?= View::e((string)$trip['trip_date']) ?><?= !empty($trip['cave_name']) ? ' · ' . View::e((string)$trip['cave_name']) : '' ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>

<?php if ($isMember): ?>
<section class="panel dashboard-shortcuts">
    <div class="panel-heading-row">
        <div>
            <h2>Quick Actions</h2>
            <p class="text-muted">Common trip-management tasks.</p>
        </div>
    </div>
    <div class="shortcut-grid">
        <a class="shortcut-card shortcut-primary" href="/trips/create"><strong>Create Trip</strong><span>Plan the next grotto trip.</span></a>
        <a class="shortcut-card" href="/caves/create"><strong>Add Cave</strong><span>Create a cave access record.</span></a>
        <a class="shortcut-card" href="/landowners/create"><strong>Add Landowner</strong><span>Add or update property contacts.</span></a>
        <?php if ($isAdmin): ?>
            <a class="shortcut-card" href="/waiver-templates"><strong>Waiver Templates</strong><span>Manage participant release language.</span></a>
            <a class="shortcut-card" href="/admin/email/settings"><strong>Email Settings</strong><span>Configure SES or SMTP and send a test.</span></a>
        <?php else: ?>
            <a class="shortcut-card" href="/trip-reports"><strong>Trip Reports</strong><span>Review cave and trip history.</span></a>
        <?php endif; ?>
    </div>
</section>
<?php else: ?>
<section class="panel dashboard-shortcuts">
    <h2>Participant Access</h2>
    <div class="shortcut-grid">
        <a class="shortcut-card shortcut-primary" href="/trips"><strong>My Trips</strong><span>View trips you are registered for.</span></a>
    </div>
</section>
<?php endif; ?>
