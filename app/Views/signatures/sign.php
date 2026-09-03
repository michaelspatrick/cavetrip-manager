<?php
use CaveTrip\Core\Csrf;
use CaveTrip\Core\View;
$alreadySigned = !empty($participant['signed_at']);
?>
<div class="signup-shell">
    <section class="panel signup-card trip-signup-hero">
        <p class="eyebrow">Digital Waiver Signature</p>
        <h1><?= View::e($participant['trip_title']) ?></h1>
        <p class="muted trip-date-line"><?= View::e($participant['trip_date']) ?><?= $participant['meeting_time'] ? ' at ' . View::e(substr((string)$participant['meeting_time'], 0, 5)) : '' ?></p>
        <dl class="signup-summary" aria-label="Signature details">
            <div class="signup-summary-item"><dt>Participant</dt><dd><?= View::e($participant['name']) ?></dd></div>
            <div class="signup-summary-item"><dt>Cave</dt><dd><?= View::e($participant['cave_name'] ?? 'Trip destination') ?></dd></div>
            <div class="signup-summary-item"><dt>Status</dt><dd><?= $alreadySigned ? 'Signed' : 'Pending Signature' ?></dd></div>
        </dl>
        <?php if ($alreadySigned): ?>
            <div class="alert success">This waiver signature was saved on <?= View::e((string)$participant['signed_at']) ?>.</div>
            <?php if (!empty($participant['signature_data'])): ?>
                <img class="saved-signature-preview" src="<?= View::e((string)$participant['signature_data']) ?>" alt="Saved signature">
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <?php if (!$alreadySigned): ?>
    <section class="panel signup-card mt">
        <h2>Sign Here</h2>
        <p class="muted">Use your finger, stylus, touchscreen, trackpad, or mouse.</p>
        <form method="post" action="/sign?token=<?= View::e($token) ?>" id="signature-form" class="form-stack">
            <?= Csrf::field() ?>
            <div class="signature-pad-wrap">
                <canvas id="signature-pad" width="1200" height="360" aria-label="Signature pad"></canvas>
            </div>
            <input type="hidden" name="signature_data" id="signature_data">
            <div class="form-actions signature-actions">
                <button class="button secondary" type="button" id="clear-signature">Clear Signature</button>
                <button class="button" type="submit">Save Signature</button>
            </div>
        </form>
    </section>
    <?php endif; ?>
</div>
<?php if (!$alreadySigned): ?>
<script>
(function () {
    const canvas = document.getElementById('signature-pad');
    const clearButton = document.getElementById('clear-signature');
    const form = document.getElementById('signature-form');
    const input = document.getElementById('signature_data');
    const ctx = canvas.getContext('2d');
    let drawing = false;
    let hasInk = false;
    let activePointerId = null;

    ctx.lineWidth = 5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#111827';

    function point(event) {
        const rect = canvas.getBoundingClientRect();
        return {
            x: (event.clientX - rect.left) * (canvas.width / rect.width),
            y: (event.clientY - rect.top) * (canvas.height / rect.height)
        };
    }

    function start(event) {
        if (event.button !== undefined && event.button !== 0) return;
        event.preventDefault();
        activePointerId = event.pointerId;
        canvas.setPointerCapture?.(event.pointerId);
        drawing = true;
        const p = point(event);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
    }

    function move(event) {
        if (!drawing || (activePointerId !== null && event.pointerId !== activePointerId)) return;
        event.preventDefault();
        const p = point(event);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        hasInk = true;
    }

    function end(event) {
        if (!drawing || (activePointerId !== null && event.pointerId !== activePointerId)) return;
        event.preventDefault();
        drawing = false;
        if (activePointerId !== null) canvas.releasePointerCapture?.(activePointerId);
        activePointerId = null;
    }

    canvas.addEventListener('pointerdown', start);
    canvas.addEventListener('pointermove', move);
    canvas.addEventListener('pointerup', end);
    canvas.addEventListener('pointercancel', end);

    clearButton.addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasInk = false;
        input.value = '';
    });

    form.addEventListener('submit', function (event) {
        if (!hasInk) {
            event.preventDefault();
            alert('Please sign before submitting.');
            return;
        }
        input.value = canvas.toDataURL('image/png');
    });
})();
</script>
<?php endif; ?>
