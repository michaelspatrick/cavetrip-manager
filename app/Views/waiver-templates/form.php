<?php
use CaveTrip\Core\Csrf;
use CaveTrip\Core\View;

$isEdit = is_array($template);
$value = static fn (string $key): string => View::e($template[$key] ?? '');
$checked = !$isEdit || (int)($template['active'] ?? 1) === 1 ? 'checked' : '';
$defaultHtml = <<<'HTML'
<h1>Landowner Liability Release</h1>

<p>{{GROTTO_NAME}} requests permission for the trip listed below.</p>

<p><strong>Landowner:</strong> {{LANDOWNER_NAME}}</p>
<p><strong>Cave / Property:</strong> {{CAVE_DESCRIPTION}}</p>
<p><strong>Trip:</strong> {{TRIP_TITLE}} on {{TRIP_DATE}}</p>

<p>Participants acknowledge the risks of cave exploration and agree to follow trip leader instructions, landowner requirements, and grotto safety practices.</p>

<h2>Participants</h2>
{{PARTICIPANT_LIST}}

<h2>Signatures</h2>
{{SIGNATURE_BLOCKS}}

<p>Finalized: {{FINALIZED_DATE}}</p>
HTML;
?>
<div class="page-header">
    <div>
        <h1><?= $isEdit ? 'Edit Waiver Template' : 'Add Waiver Template' ?></h1>
        <p class="text-muted">Use placeholders to merge trip, grotto, landowner, cave, participant, and signature data.</p>
    </div>
    <a class="button secondary" href="/waiver-templates">Back to Templates</a>
</div>

<form method="post" action="<?= View::e($action) ?>" class="panel form-stack">
    <?= Csrf::field() ?>

    <label>
        Name
        <input type="text" name="name" required value="<?= $value('name') ?>" placeholder="Landowner Liability Release">
    </label>

    <label>
        Slug
        <input type="text" name="slug" value="<?= $value('slug') ?>" placeholder="landowner-liability-release">
        <small class="help-text">Leave blank to generate from the name.</small>
    </label>

    <label>
        Description
        <textarea name="description" rows="4" placeholder="When this waiver should be used."><?= $value('description') ?></textarea>
    </label>

    <label>
        HTML Body
        <textarea name="html_body" rows="22" required class="code-editor"><?= $isEdit ? $value('html_body') : View::e($defaultHtml) ?></textarea>
    </label>

    <label class="checkbox-row">
        <input type="checkbox" name="active" value="1" <?= $checked ?>>
        Active
    </label>

    <div class="panel helper-panel">
        <h2>Available Placeholders</h2>
        <p class="text-muted">Copy these into the HTML body. They will be replaced when a trip waiver is finalized.</p>

        <div class="placeholder-list">
            <?php foreach ($placeholders as $placeholder): ?>
                <code onclick="navigator.clipboard && navigator.clipboard.writeText(this.textContent)"><?= View::e($placeholder) ?></code>
            <?php endforeach; ?>
        </div>

        <h3>Notes</h3>
        <ul class="check-list">
            <li>Keep legal language in the template body.</li>
            <li>Use <code>{{PARTICIPANT_LIST}}</code> where printed names should appear.</li>
            <li>Use <code>{{SIGNATURE_BLOCKS}}</code> where signature images and timestamps should appear.</li>
            <li>Have an attorney review waiver language before relying on it.</li>
        </ul>
    </div>

    <div class="form-actions">
        <button type="submit" class="button primary"><?= $isEdit ? 'Save Template' : 'Create Template' ?></button>
        <a class="button secondary" href="/waiver-templates">Cancel</a>
    </div>
</form>
