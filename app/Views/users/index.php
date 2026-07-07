<section class="panel">
    <div class="section-header">
        <div>
            <h1>Users</h1>
            <p class="muted">Manage admins, members, and guests for your grotto.</p>
        </div>
        <a class="button primary" href="/users/create">Create User</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Grotto</th>
                    <th>Active</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= e($user['name']) ?></td>
                    <td><?= e($user['email']) ?></td>
                    <td><span class="badge"><?= e($user['role']) ?></span></td>
                    <td><?= e($user['grotto_name'] ?? (string)($user['grotto_id'] ?? '')) ?></td>
                    <td><?= ((int)$user['active'] === 1) ? 'Yes' : 'No' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
