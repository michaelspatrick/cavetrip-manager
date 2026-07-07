<?php
use CaveTrip\Core\Csrf;
use CaveTrip\Core\View;

$isEdit = is_array($landowner);
$value = static fn (string $key): string => View::e($landowner[$key] ?? '');
$checked = !$isEdit || (int)($landowner['active'] ?? 1) === 1 ? 'checked' : '';
?>
<div class="page-header">
    <div>
        <h1><?= $isEdit ? 'Edit Landowner' : 'Add Landowner' ?></h1>
        <p>Landowner records are scoped to your grotto.</p>
    </div>
    <a class="button secondary" href="/landowners">Back to Landowners</a>
</div>

<form method="post" action="<?= View::e($action) ?>" class="panel form-stack">
    <?= Csrf::field() ?>

    <div class="form-grid">
        <label>Name
            <input type="text" name="name" required value="<?= $value('name') ?>">
        </label>
        <label>Email
            <input type="email" name="email" value="<?= $value('email') ?>">
        </label>
        <label>Phone
            <input type="text" name="phone" value="<?= $value('phone') ?>">
        </label>
        <label>Preferred Contact Method
            <input type="text" name="preferred_contact_method" placeholder="Phone, email, text, etc." value="<?= $value('preferred_contact_method') ?>">
        </label>
    </div>

    <label>Mailing Address
        <textarea name="mailing_address" rows="3"><?= $value('mailing_address') ?></textarea>
    </label>

    <label>Notes
        <textarea name="notes" rows="5" placeholder="Access preferences, seasonal restrictions, relationship notes, etc."><?= $value('notes') ?></textarea>
    </label>

    <label class="checkbox-row">
        <input type="checkbox" name="active" value="1" <?= $checked ?>> Active
    </label>

    <div class="button-row">
        <button type="submit" class="button primary"><?= $isEdit ? 'Save Landowner' : 'Create Landowner' ?></button>
    </div>
</form>
