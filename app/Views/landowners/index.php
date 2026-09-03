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
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($landowners as $landowner): ?>
                <tr>
                    <td><strong><?= View::e((string)$landowner['name']) ?></strong></td>
                    <td><?= View::e((string)($landowner['email'] ?? '')) ?></td>
                    <td><?= View::e((string)($landowner['phone'] ?? '')) ?></td>
                    <td><?= View::e((string)($landowner['preferred_contact_method'] ?? '')) ?></td>
                    <td><a href="/landowners/edit?id=<?= (int)$landowner['id'] ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($landowners === []): ?>
                <tr><td colspan="5" class="muted">No landowners have been added yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
