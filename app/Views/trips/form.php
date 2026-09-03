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
        <p>Plan the trip, attendee limits, waiver, and emergency callout details.</p>
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
            <?php foreach (['core_group' => 'Members', 'selected_members' => 'Selected Users', 'invite_link' => 'Invite Link', 'private' => 'Private'] as $value => $label): ?>
                <option value="<?= $value ?>" <?= (($trip['visibility'] ?? 'core_group') === $value) ? 'selected' : '' ?>><?= View::e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <small class="text-muted">Members: visible to grotto members. Selected Users: only the leader, admins, and people explicitly added to the trip. Invite Link: hidden from trip lists and accessible through its signup link. Private: only the leader and admins can see it until participants are explicitly added.</small>
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
        Callout Date / Time
        <input type="datetime-local" name="callout_time" required value="<?= View::e($datetimeValue($trip['callout_time'] ?? null)) ?>">
    </label>


    <fieldset class="form-section">
        <legend>Callout Contacts</legend>
        <p class="text-muted">Specify at least one person who should expect the trip to be out by the callout time.</p>
        <?php
        $calloutRows = $calloutContacts ?? [];
        while (count($calloutRows) < 3) { $calloutRows[] = ['name'=>'','email'=>'','phone'=>'']; }
        foreach ($calloutRows as $contact):
        ?>
        <div class="callout-contact-row">
            <label>Name<input type="text" name="callout_contact_name[]" value="<?= View::e((string)($contact['name'] ?? '')) ?>"></label>
            <label>Email<input type="email" name="callout_contact_email[]" value="<?= View::e((string)($contact['email'] ?? '')) ?>"></label>
            <label>Phone<input type="text" name="callout_contact_phone[]" value="<?= View::e((string)($contact['phone'] ?? '')) ?>"></label>
        </div>
        <?php endforeach; ?>
    </fieldset>

    <label class="checkbox-row">
        <input type="checkbox" name="waitlist_enabled" value="1" <?= ((int)($trip['waitlist_enabled'] ?? 1) === 1) ? 'checked' : '' ?>>
        Enable waitlist when max attendees is reached
    </label>

    <label class="full-width">
        Meeting Location / Public Instructions
        <textarea name="meeting_location" rows="3"><?= View::e($trip['meeting_location'] ?? '') ?></textarea>
    </label>

    <label class="full-width">
        Trip Leader / Internal Notes
        <textarea name="notes" rows="4"><?= View::e($trip['notes'] ?? '') ?></textarea>
        <small class="text-muted">Visible only to the trip leader and administrators. Not shown on public signup pages or waivers.</small>
    </label>

    <div class="form-actions full-width">
        <button type="submit" class="button">Save Trip</button>
    </div>
</form>
