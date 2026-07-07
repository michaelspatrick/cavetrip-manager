<?php
use CaveTrip\Core\View;
?>
<div class="page-header">
    <div>
        <h1>Waiver Templates</h1>
        <p>Manage reusable HTML waiver language for this grotto. Generated waivers will store a final rendered copy later, so old records are preserved after template edits.</p>
    </div>
    <a class="button" href="/waiver-templates/create">Add Template</a>
</div>

<div class="panel">
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Description</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($templates as $template): ?>
                <tr>
                    <td><strong><?= View::e($template['name']) ?></strong></td>
                    <td><code><?= View::e($template['slug']) ?></code></td>
                    <td><?= View::e($template['description'] ?? '') ?></td>
                    <td><span class="badge"><?= ((int)$template['active'] === 1) ? 'Active' : 'Inactive' ?></span></td>
                    <td><a href="/waiver-templates/edit?id=<?= (int)$template['id'] ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($templates === []): ?>
                <tr><td colspan="5" class="muted">No waiver templates have been added yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
