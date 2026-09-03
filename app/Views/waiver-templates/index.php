<?php
use CaveTrip\Core\View;
?>
<div class="page-header">
    <div>
        <h1>Waiver Templates</h1>
        <p>Manage reusable HTML waiver language for this grotto. Finalized waivers retain the exact rendered copy used for signing.</p>
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
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($templates as $template): ?>
                <tr>
                    <td><strong><?= View::e((string)$template['name']) ?></strong></td>
                    <td><code><?= View::e((string)$template['slug']) ?></code></td>
                    <td><?= View::e((string)($template['description'] ?? '')) ?></td>
                    <td><a href="/waiver-templates/edit?id=<?= (int)$template['id'] ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($templates === []): ?>
                <tr><td colspan="4" class="muted">No waiver templates have been added yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
