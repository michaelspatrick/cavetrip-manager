<?php
use CaveTrip\Core\Csrf;
use CaveTrip\Core\View;
$alreadySigned = !empty($participant['signed_at']);
?>
<div class="signup-shell">
    <section class="panel signup-card">
        <p class="eyebrow">Digital Waiver Signature</p>
        <h1><?= View::e($participant['trip_title']) ?></h1>
        <p class="muted"><?= View::e($participant['trip_date']) ?><?= $participant['meeting_time'] ? ' at ' . View::e(substr((string)$participant['meeting_time'], 0, 5)) : '' ?></p>
        <div class="signup-summary">
            <div><span>Participant</span><strong><?= View::e($participant['name']) ?></strong></div>
            <div><span>Cave</span><strong><?= View::e($participant['cave_name'] ?? 'Trip destination') ?></strong></div>
            <div><span>Status</span><strong><?= $alreadySigned ? 'Signed' : 'Pending Signature' ?></strong></div>
        </div>
        <?php if ($alreadySigned): ?>
            <div class="alert success">This waiver signature was saved on <?= View::e((string)$participant['signed_at']) ?>.</div>
            <?php if (!empty($participant['signature_data'])): ?>
                <img class="saved-signature-preview" src="<?= View::e((string)$participant['signature_data']) ?>" alt="Saved signature">
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <section class="panel signup-card mt">
        <h2>Sign Here</h2>
        <p class="muted">Use your finger, stylus, or mouse. The saved signature is attached to the final trip waiver.</p>
        <form method="post" action="/sign?token=<?= View::e($token) ?>" id="signature-form" class="form-stack">
            <?= Csrf::field() ?>
            <div class="signature-pad-wrap">
                <canvas id="signature-pad" width="900" height="280"></canvas>
            </div>
            <input type="hidden" name="signature_data" id="signature_data">
            <div class="form-actions">
                <button class="button secondary" type="button" id="clear-signature">Clear Signature</button>
                <button class="button" type="submit">Save Signature</button>
            </div>
        </form>
    </section>
</div>
<script>
(function () {
    const canvas = document.getElementById('signature-pad');
    const clearButton = document.getElementById('clear-signature');
    const form = document.getElementById('signature-form');
    const input = document.getElementById('signature_data');
    const ctx = canvas.getContext('2d');
    let drawing = false;
    let hasInk = false;

    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
    }

    function position(event) {
        const rect = canvas.getBoundingClientRect();
        const point = event.touches ? event.touches[0] : event;
        return { x: point.clientX - rect.left, y: point.clientY - rect.top };
    }

    function start(event) {
        event.preventDefault();
        drawing = true;
        hasInk = true;
        const p = position(event);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
    }

    function move(event) {
        if (!drawing) return;
        event.preventDefault();
        const p = position(event);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
    }

    function end(event) {
        if (!drawing) return;
        event.preventDefault();
        drawing = false;
    }

    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);
    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    canvas.addEventListener('mouseup', end);
    canvas.addEventListener('mouseleave', end);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('touchend', end, { passive: false });

    clearButton.addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasInk = false;
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
