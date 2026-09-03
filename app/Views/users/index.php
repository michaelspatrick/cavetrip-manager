<?php use CaveTrip\Core\View; ?>
<div class="page-header">
    <div><h1>Users</h1><p>Manage administrators, members, and guests.</p></div>
    <a class="button" href="/users/create">Create User</a>
</div>
<div class="table-wrap">
<table>
<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Active</th><th>Last Login</th><th></th></tr></thead>
<tbody>
<?php foreach ($users as $user): ?>
<tr>
<td><strong><?= View::e((string)$user['name']) ?></strong></td>
<td><?= View::e((string)$user['email']) ?></td>
<td><?= View::e((string)($user['phone'] ?? '')) ?></td>
<td><?= View::e((string)$user['role']) ?></td>
<td><?= (int)$user['active'] === 1 ? 'Yes' : 'No' ?></td>
<td><?= View::e((string)($user['last_login_at'] ?? 'Never')) ?></td>
<td><a href="/users/edit?id=<?= (int)$user['id'] ?>">Edit</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
