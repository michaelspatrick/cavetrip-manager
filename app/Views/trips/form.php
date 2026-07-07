<?php
use CaveTrip\Core\Csrf;
use CaveTrip\Core\View;

$trip = $trip ?? [];
$datetimeValue = static function (?string $value): string {
    if (!$value) { return ''; }
    return str_replace(' ', 'T', substr($value, 0, 16));
};
?>
<div class="page-header">
    <div>
        <h1><?= View::e($title) ?></h1>
        <p>Create a trip shell now; participant signup, waiver signatures, and notifications come next.</p>
    </div>
    <a class="button secondary" href="/trips">Back to Trips</a>
</div>

<form method="post" action="<?= View::e($action) ?>" class="card form-grid">
    <?= Csrf::field() ?>

    <label>
        Trip Title
        <input type="text" name="title" required value="<?= View::e($trip['title'] ?? '') ?>">
    </label>

    <label>
        Trip Date
        <input type="date" name="trip_date" required value="<?= View::e($trip['trip_date'] ?? '') ?>">
    </label>

    <label>
        Meeting Time
        <input type="time" name="meeting_time" value="<?= View::e(substr((string)($trip['meeting_time'] ?? ''), 0, 5)) ?>">
    </label>

    <label>
        Status
        <select name="status">
            <?php foreach (['draft','open','waiver_signing','finalized','active','completed','cancelled'] as $status): ?>
                <option value="<?= $status ?>" <?= (($trip['status'] ?? 'draft') === $status) ? 'selected' : '' ?>><?= View::e($status) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>
        Cave
        <select name="cave_id">
            <option value="">No cave selected</option>
            <?php foreach ($caves as $cave): ?>
                <option value="<?= (int)$cave['id'] ?>" <?= ((int)($trip['cave_id'] ?? 0) === (int)$cave['id']) ? 'selected' : '' ?>><?= View::e($cave['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>
        Landowner
        <select name="landowner_id">
            <option value="">No landowner selected</option>
            <?php foreach ($landowners as $landowner): ?>
                <option value="<?= (int)$landowner['id'] ?>" <?= ((int)($trip['landowner_id'] ?? 0) === (int)$landowner['id']) ? 'selected' : '' ?>><?= View::e($landowner['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>
        Waiver Template
        <select name="waiver_template_id">
            <option value="">No waiver selected yet</option>
            <?php foreach ($waiverTemplates as $template): ?>
                <option value="<?= (int)$template['id'] ?>" <?= ((int)($trip['waiver_template_id'] ?? 0) === (int)$template['id']) ? 'selected' : '' ?>><?= View::e($template['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>
        Visibility
        <select name="visibility">
            <?php foreach (['core_group' => 'Core Group', 'selected_members' => 'Selected Members', 'invite_link' => 'Invite Link', 'private' => 'Private'] as $value => $label): ?>
                <option value="<?= $value ?>" <?= (($trip['visibility'] ?? 'core_group') === $value) ? 'selected' : '' ?>><?= View::e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>
        Minimum Attendees
        <input type="number" min="0" name="min_attendees" value="<?= View::e((string)($trip['min_attendees'] ?? '')) ?>">
    </label>

    <label>
        Maximum Attendees
        <input type="number" min="1" name="max_attendees" value="<?= View::e((string)($trip['max_attendees'] ?? '')) ?>">
    </label>

    <label>
        Signup Opens
        <input type="datetime-local" name="signup_opens_at" value="<?= View::e($datetimeValue($trip['signup_opens_at'] ?? null)) ?>">
    </label>

    <label>
        Signup Closes
        <input type="datetime-local" name="signup_closes_at" value="<?= View::e($datetimeValue($trip['signup_closes_at'] ?? null)) ?>">
    </label>

    <label>
        Callout Time
        <input type="datetime-local" name="callout_time" value="<?= View::e($datetimeValue($trip['callout_time'] ?? null)) ?>">
    </label>

    <label class="checkbox-row">
        <input type="checkbox" name="waitlist_enabled" value="1" <?= ((int)($trip['waitlist_enabled'] ?? 1) === 1) ? 'checked' : '' ?>>
        Enable waitlist when max attendees is reached
    </label>

    <label class="full-width">
        Meeting Location / Public Instructions
        <textarea name="meeting_location" rows="3"><?= View::e($trip['meeting_location'] ?? '') ?></textarea>
    </label>

    <label class="full-width">
        Cave / Permission Area Description
        <textarea name="cave_description" rows="3"><?= View::e($trip['cave_description'] ?? '') ?></textarea>
    </label>

    <label class="full-width">
        Leader Notes
        <textarea name="notes" rows="4"><?= View::e($trip['notes'] ?? '') ?></textarea>
    </label>

    <div class="form-actions full-width">
        <button type="submit" class="button">Save Trip</button>
    </div>
</form>
