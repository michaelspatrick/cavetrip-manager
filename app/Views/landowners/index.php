<?php
use CaveTrip\Core\View;
?>
<div class="page-header">
    <div>
        <h1>Landowners</h1>
        <p>Manage landowner contact records for your grotto.</p>
    </div>
    <a class="button" href="/landowners/create">Add Landowner</a>
</div>

<div class="panel">
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Preferred Contact</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($landowners as $landowner): ?>
                <tr>
                    <td><strong><?= View::e($landowner['name']) ?></strong></td>
                    <td><?= View::e($landowner['email'] ?? '') ?></td>
                    <td><?= View::e($landowner['phone'] ?? '') ?></td>
                    <td><?= View::e($landowner['preferred_contact_method'] ?? '') ?></td>
                    <td><span class="badge"><?= ((int)$landowner['active'] === 1) ? 'Active' : 'Inactive' ?></span></td>
                    <td><a href="/landowners/edit?id=<?= (int)$landowner['id'] ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($landowners === []): ?>
                <tr><td colspan="6" class="muted">No landowners have been added yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
