<?php use CaveTrip\Core\View; ?>
<div class="page-header"><div><h1>Caves</h1><p>Cave records and access information for trip planning.</p></div><a class="button" href="/caves/create">Add Cave</a></div>
<div class="panel"><div class="table-wrap"><table>
<thead><tr><th>Name</th><th>Landowner</th><th>County</th><th>State</th><th></th></tr></thead>
<tbody>
<?php foreach($caves as $cave): ?><tr><td><strong><?= View::e($cave['name']) ?></strong></td><td><?= View::e($cave['landowner_name']??'') ?></td><td><?= View::e($cave['county']??'') ?></td><td><?= View::e($cave['state']??'') ?></td><td><a href="/caves/edit?id=<?= (int)$cave['id'] ?>">Edit</a></td></tr><?php endforeach; ?>
<?php if($caves===[]): ?><tr><td colspan="5" class="muted">No caves have been added yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
