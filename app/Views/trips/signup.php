<?php
use CaveTrip\Core\Csrf;
use CaveTrip\Core\View;
$activeCount = (int)($trip['registered_count'] ?? 0);
$max = $trip['max_attendees'] === null ? null : (int)$trip['max_attendees'];
$requiresWaiver = is_array($waiver ?? null);
?>
<div class="signup-shell">
    <section class="panel signup-card trip-signup-hero">
        <p class="eyebrow">Trip Signup</p>
        <h1><?= View::e($trip['title']) ?></h1>
        <p class="muted trip-date-line"><?= View::e($trip['trip_date']) ?><?= $trip['meeting_time'] ? ' at ' . View::e(substr((string)$trip['meeting_time'], 0, 5)) : '' ?></p>

        <dl class="signup-summary" aria-label="Trip details">
            <div class="signup-summary-item"><dt>Cave</dt><dd><?= View::e($trip['cave_name'] ?? 'Trip destination') ?></dd></div>
            <div class="signup-summary-item"><dt>Leader</dt><dd><?= View::e($trip['leader_name'] ?? 'Trip leader') ?></dd></div>
            <div class="signup-summary-item"><dt>Roster</dt><dd><?= $activeCount ?><?= $max ? ' / ' . $max : '' ?></dd></div>
        </dl>

        <?php if (!empty($trip['meeting_location'])): ?>
            <div class="signup-detail-block"><strong>Meeting location</strong><span><?= View::e((string)$trip['meeting_location']) ?></span></div>
        <?php endif; ?>
    </section>

    <form method="post" action="/trip/signup?token=<?= View::e($token) ?>" id="trip-signup-form">
        <?= Csrf::field() ?>

        <section class="panel signup-card mt">
            <p class="eyebrow">Step 1</p>
            <h2>Your Information</h2>
            <p class="muted">Enter the participant and emergency information the trip leader needs.</p>
            <div class="form-grid signup-fields">
                <?php require __DIR__ . '/participant-fields.php'; ?>
            </div>
        </section>

        <?php if ($requiresWaiver): ?>
        <section class="panel signup-card mt waiver-signup-section">
            <p class="eyebrow">Step 2</p>
            <h2><?= View::e((string)$waiver['name']) ?></h2>
            <p class="muted">Read the complete waiver before signing.</p>
            <div class="waiver-reading-pane" tabindex="0" aria-label="Waiver text">
                <?= $waiver['html'] ?>
            </div>

            <label class="checkbox-row waiver-acknowledgement">
                <input type="checkbox" name="waiver_acknowledged" value="1" required>
                <span>I have read and agree to the waiver above. If the participant is a minor, I confirm that I am the parent or legal guardian authorized to sign for the minor.</span>
            </label>

            <div class="signature-heading">
                <h3 id="signature-title">Participant Signature</h3>
                <p class="muted" id="signature-help">Sign using your finger, stylus, touchscreen, trackpad, or mouse.</p>
            </div>
            <div class="signature-pad-wrap">
                <canvas id="signature-pad" width="1200" height="360" aria-label="Waiver signature pad"></canvas>
            </div>
            <input type="hidden" name="signature_data" id="signature_data">
            <div class="form-actions signature-actions">
                <button class="button secondary" type="button" id="clear-signature">Clear Signature</button>
            </div>
        </section>
        <?php endif; ?>

        <section class="panel signup-card mt signup-submit-card">
            <p class="eyebrow"><?= $requiresWaiver ? 'Step 3' : 'Step 2' ?></p>
            <h2>Complete Registration</h2>
            <p class="muted"><?= $requiresWaiver ? 'Submitting saves your trip registration and digital waiver signature together.' : 'Submit your information to join this trip.' ?></p>
            <div class="form-actions">
                <button class="button" type="submit"><?= $requiresWaiver ? 'Sign Waiver & Join Trip' : 'Join Trip' ?></button>
            </div>
        </section>
    </form>
</div>
<?php if ($requiresWaiver): ?>
<script>
(function () {
    const canvas = document.getElementById('signature-pad');
    const clearButton = document.getElementById('clear-signature');
    const form = document.getElementById('trip-signup-form');
    const input = document.getElementById('signature_data');
    const minor = document.getElementById('is_minor');
    const signatureTitle = document.getElementById('signature-title');
    const signatureHelp = document.getElementById('signature-help');
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
        return {x:(event.clientX-rect.left)*(canvas.width/rect.width), y:(event.clientY-rect.top)*(canvas.height/rect.height)};
    }
    function start(event) {
        if (event.button !== undefined && event.button !== 0) return;
        event.preventDefault(); activePointerId=event.pointerId; canvas.setPointerCapture?.(event.pointerId); drawing=true;
        const p=point(event); ctx.beginPath(); ctx.moveTo(p.x,p.y);
    }
    function move(event) {
        if (!drawing || (activePointerId !== null && event.pointerId !== activePointerId)) return;
        event.preventDefault(); const p=point(event); ctx.lineTo(p.x,p.y); ctx.stroke(); hasInk=true;
    }
    function end(event) {
        if (!drawing || (activePointerId !== null && event.pointerId !== activePointerId)) return;
        event.preventDefault(); drawing=false; if(activePointerId!==null) canvas.releasePointerCapture?.(activePointerId); activePointerId=null;
    }
    function clearSignature() { ctx.clearRect(0,0,canvas.width,canvas.height); hasInk=false; input.value=''; }
    function syncSignerLabel() {
        const guardianSigning = minor && minor.checked;
        signatureTitle.textContent = guardianSigning ? 'Parent / Guardian Signature' : 'Participant Signature';
        signatureHelp.textContent = guardianSigning
            ? 'The parent or legal guardian named above must sign for the minor participant.'
            : 'Sign using your finger, stylus, touchscreen, trackpad, or mouse.';
        clearSignature();
    }

    canvas.addEventListener('pointerdown',start);
    canvas.addEventListener('pointermove',move);
    canvas.addEventListener('pointerup',end);
    canvas.addEventListener('pointercancel',end);
    clearButton.addEventListener('click',clearSignature);
    minor?.addEventListener('change',syncSignerLabel);
    syncSignerLabel();

    form.addEventListener('submit',function(event){
        if(!hasInk){event.preventDefault();alert('Please sign the waiver before completing registration.');canvas.scrollIntoView({behavior:'smooth',block:'center'});return;}
        input.value=canvas.toDataURL('image/png');
    });
})();
</script>
<?php endif; ?>
