<?php
use CaveTrip\Core\Csrf;
use CaveTrip\Core\View;
$isEdit = is_array($user);
$value = static fn(string $key): string => View::e((string)($user[$key] ?? ''));
$role = (string)($user['role'] ?? 'guest');
$selectedGrottoId = (string)($user['grotto_id'] ?? '');
?>
<div class="page-header">
<div><h1><?= $isEdit ? 'Edit User' : 'Create User' ?></h1><p>New accounts default to Guest until an administrator promotes them.</p></div>
<a class="button secondary" href="/users">Back to Users</a>
</div>
<form method="post" action="<?= View::e($action) ?>" class="panel form-stack">
<?= Csrf::field() ?>
<?php if ((string)$currentUser['role'] === 'super_admin'): ?>
<label>Grotto
<select name="grotto_id">
    <option value="" <?= $selectedGrottoId === '' ? 'selected' : '' ?>>No grotto (Super Admin only)</option>
    <?php foreach ($grottos as $grotto): ?>
        <option value="<?= (int)$grotto['id'] ?>" <?= $selectedGrottoId === (string)$grotto['id'] ? 'selected' : '' ?>>
            <?= View::e((string)$grotto['name']) ?><?= (int)($grotto['active'] ?? 1) === 1 ? '' : ' (Inactive)' ?>
        </option>
    <?php endforeach; ?>
</select>
<small class="text-muted">Admin, Member, and Guest accounts must belong to a grotto. Installation-level Super Admin accounts may be unassigned.</small>
</label>
<?php endif; ?>
<label>Name<input type="text" name="name" required value="<?= $value('name') ?>"></label>
<label>Email<input type="email" name="email" required value="<?= $value('email') ?>"></label>
<label>Phone<input type="text" name="phone" value="<?= $value('phone') ?>"></label>
<label>Role
<select name="role" required>
<?php if ((string)$currentUser['role'] === 'super_admin'): ?><option value="super_admin" <?= $role === 'super_admin' ? 'selected' : '' ?>>Super Admin</option><?php endif; ?>
<option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
<option value="member" <?= $role === 'member' ? 'selected' : '' ?>>Member</option>
<option value="guest" <?= $role === 'guest' ? 'selected' : '' ?>>Guest</option>
</select>
</label>
<label class="checkbox-row"><input type="checkbox" name="active" value="1" <?= !$isEdit || (int)($user['active'] ?? 1) === 1 ? 'checked' : '' ?>> Active account</label>
<label><?= $isEdit ? 'Set New Password' : 'Password' ?>
<input type="password" name="password" autocomplete="new-password">
<small class="text-muted"><?= $isEdit ? 'Leave blank to keep the current password. New passwords must be at least 12 characters.' : 'A password can be added later for a guest account.' ?></small>
</label>
<?php if ($isEdit): ?>
<div class="profile-meta"><span>Created: <?= $value('created_at') ?></span><span>Last login: <?= $value('last_login_at') ?: 'Never' ?></span></div>
<?php endif; ?>
<div class="form-actions"><button type="submit" class="button"><?= $isEdit ? 'Save User' : 'Create User' ?></button><a class="button secondary" href="/users">Cancel</a></div>
</form>
