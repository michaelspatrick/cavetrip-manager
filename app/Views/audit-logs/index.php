<?php
use CaveTrip\Core\View;
?>

<div class="page-header">
    <div>
        <h1>Audit Log</h1>
        <p class="text-muted">Recent system activity and administrative events.</p>
    </div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Event</th>
                <th>Entity</th>
                <th>Message</th>
                <th>User ID</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($logs === []): ?>
                <tr>
                    <td colspan="6">No audit log entries yet.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= View::e($log['created_at'] ?? '') ?></td>
                    <td><?= View::e($log['event_type'] ?? '') ?></td>
                    <td>
                        <?php if (!empty($log['entity_type'])): ?>
                            <?= View::e($log['entity_type']) ?>
                            <?php if (!empty($log['entity_id'])): ?>
                                #<?= (int)$log['entity_id'] ?>
                            <?php endif; ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td><?= View::e($log['message'] ?? '') ?></td>
                    <td><?= View::e((string)($log['user_id'] ?? '')) ?></td>
                    <td><?= View::e($log['ip_address'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
