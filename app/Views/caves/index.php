<?php
use CaveTrip\Core\View;
?>
<div class="page-header">
    <div>
        <h1>Caves</h1>
        <p>Admin-only cave records, including sensitive location and access information.</p>
    </div>
    <a class="button" href="/caves/create">Add Cave</a>
</div>

<div class="alert error">
    Cave location, GPS, gate, access, and sensitive notes are restricted to grotto admins in this release.
</div>

<div class="panel">
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Landowner</th>
                <th>County</th>
                <th>General Area</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($caves as $cave): ?>
                <tr>
                    <td><strong><?= View::e($cave['name']) ?></strong></td>
                    <td><?= View::e($cave['landowner_name'] ?? '') ?></td>
                    <td><?= View::e($cave['county'] ?? '') ?></td>
                    <td><?= View::e($cave['general_area'] ?? '') ?></td>
                    <td><span class="badge"><?= ((int)$cave['active'] === 1) ? 'Active' : 'Inactive' ?></span></td>
                    <td><a href="/caves/edit?id=<?= (int)$cave['id'] ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($caves === []): ?>
                <tr><td colspan="6" class="muted">No caves have been added yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
