<?php
use CaveTrip\Core\Csrf;
use CaveTrip\Core\View;

$isEdit = is_array($cave);
$value = static fn (string $key): string => View::e($cave[$key] ?? '');
$selectedLandownerId = (int)($cave['landowner_id'] ?? 0);
$checked = !$isEdit || (int)($cave['active'] ?? 1) === 1 ? 'checked' : '';
?>
<div class="page-header">
    <div>
        <h1><?= $isEdit ? 'Edit Cave' : 'Add Cave' ?></h1>
        <p>Store cave, landowner, access, and sensitive rescue/location information.</p>
    </div>
    <a class="button secondary" href="/caves">Back to Caves</a>
</div>

<form method="post" action="<?= View::e($action) ?>" class="panel form-stack">
    <?= Csrf::field() ?>

    <div class="form-grid">
        <label>Cave Name
            <input type="text" name="name" required value="<?= $value('name') ?>">
        </label>
        <label>Landowner
            <select name="landowner_id">
                <option value="">No landowner selected</option>
                <?php foreach ($landowners as $landowner): ?>
                    <option value="<?= (int)$landowner['id'] ?>" <?= (int)$landowner['id'] === $selectedLandownerId ? 'selected' : '' ?>>
                        <?= View::e($landowner['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>County
            <input type="text" name="county" value="<?= $value('county') ?>">
        </label>
        <label>General Area
            <input type="text" name="general_area" value="<?= $value('general_area') ?>">
        </label>
        <label>GPS Latitude
            <input type="text" name="gps_latitude" placeholder="36.1234567" value="<?= $value('gps_latitude') ?>">
        </label>
        <label>GPS Longitude
            <input type="text" name="gps_longitude" placeholder="-82.1234567" value="<?= $value('gps_longitude') ?>">
        </label>
    </div>

    <label>Access Directions
        <textarea name="access_directions" rows="4" placeholder="Driving directions, driveway/gate details, trail approach, etc."><?= $value('access_directions') ?></textarea>
    </label>

    <label>Parking Notes
        <textarea name="parking_notes" rows="3"><?= $value('parking_notes') ?></textarea>
    </label>

    <label>Access Notes
        <textarea name="access_notes" rows="4" placeholder="Landowner requirements, seasonal restrictions, equipment requirements, etc."><?= $value('access_notes') ?></textarea>
    </label>

    <label>Gate / Lock Code
        <input type="text" name="gate_code" value="<?= $value('gate_code') ?>">
        <small>Restricted. This should only appear to authorized leaders/admins and in emergency packets.</small>
    </label>

    <label>Sensitive Notes
        <textarea name="sensitive_notes" rows="5" placeholder="Sensitive location, conservation, rescue, or landowner notes."><?= $value('sensitive_notes') ?></textarea>
    </label>

    <label class="checkbox-row">
        <input type="checkbox" name="active" value="1" <?= $checked ?>> Active
    </label>

    <div class="button-row">
        <button type="submit" class="button primary"><?= $isEdit ? 'Save Cave' : 'Create Cave' ?></button>
    </div>
</form>
