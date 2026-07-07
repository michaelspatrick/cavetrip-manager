<?php use CaveTrip\Core\Csrf; ?>
<section class="panel narrow">
    <h1>Sign In</h1>
    <p class="muted">Use the admin account created with <code>php tools/create_admin.php</code>.</p>
    <form method="post" action="/login" class="form-stack">
        <?= Csrf::field() ?>
        <label>Email
            <input type="email" name="email" required autocomplete="email">
        </label>
        <label>Password
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button type="submit" class="button primary">Sign In</button>
    </form>
</section>
