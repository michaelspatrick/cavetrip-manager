<?php use CaveTrip\Core\Csrf; ?>
<section class="panel narrow">
    <h1>Create User</h1>
    <form method="post" action="/users" class="form-stack">
        <?= Csrf::field() ?>
        <?php if ($currentUser['role'] === 'super_admin'): ?>
            <label>Grotto ID
                <input type="number" name="grotto_id" min="1" placeholder="Leave blank for super admin">
            </label>
        <?php endif; ?>
        <label>Name
            <input type="text" name="name" required>
        </label>
        <label>Email
            <input type="email" name="email" required>
        </label>
        <label>Phone
            <input type="text" name="phone">
        </label>
        <label>Role
            <select name="role" required>
                <?php if ($currentUser['role'] === 'super_admin'): ?>
                    <option value="super_admin">Super Admin</option>
                    <option value="grotto_admin">Grotto Admin</option>
                <?php endif; ?>
                <option value="member">Member</option>
                <option value="guest">Guest</option>
            </select>
        </label>
        <label>Password
            <input type="password" name="password" autocomplete="new-password">
            <small>Guests can be created without a password for now.</small>
        </label>
        <button type="submit" class="button primary">Create User</button>
    </form>
</section>
