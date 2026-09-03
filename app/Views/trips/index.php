<?php use CaveTrip\Core\View; ?>
<div class="page-header">
    <div><h1><?= View::e($title ?? 'Trips') ?></h1><p>Plan cave trips, control signup capacity, and prepare for waiver collection.</p></div>
    <?php if (($currentUser['role'] ?? '') !== 'guest'): ?><a class="button" href="/trips/create">Create Trip</a><?php endif; ?>
</div>
<?php if ($trips === []): ?>
<div class="card"><p>No trips have been created yet.</p></div>
<?php else: ?>
<div class="card table-card"><div class="table-wrap"><table>
<thead><tr><th>Date</th><th>Trip</th><th>Cave</th><th>Capacity</th><th>Signed</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php foreach($trips as $trip): $status=(string)($trip['status']??'draft'); $label=['draft'=>'Draft','open'=>'Open','closed'=>'Closed','completed'=>'Completed','cancelled'=>'Cancelled'][$status]??ucwords(str_replace('_',' ',$status)); ?>
<tr>
<td><?= View::e($trip['trip_date']) ?></td>
<td><strong><?= View::e($trip['title']) ?></strong><br><small><?= View::e($trip['trip_number']) ?> · Leader: <?= View::e($trip['leader_name']??'Unassigned') ?></small>
<?php if(!empty($trip['share_token']) && ($currentUser['role']??'guest')!=='guest'): ?><br><a class="signup-link" href="/trip/signup?token=<?= urlencode((string)$trip['share_token']) ?>" target="_blank" rel="noopener">Signup Link ↗</a><?php endif; ?></td>
<td><?= View::e($trip['cave_name']??'Not selected') ?></td>
<td><?= (int)$trip['registered_count'] ?><?= $trip['max_attendees']?' / '.(int)$trip['max_attendees']:'' ?><?php if((int)$trip['waitlist_count']>0): ?><br><small><?= (int)$trip['waitlist_count'] ?> waitlisted</small><?php endif; ?></td>
<td><?= (int)$trip['signed_count'] ?> / <?= (int)$trip['registered_count'] ?></td>
<td><span class="status-badge status-<?= View::e($status) ?>"><?= View::e($label) ?></span></td>
<td><a href="/trips/show?id=<?= (int)$trip['id'] ?>">Manage</a></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?php endif; ?>
