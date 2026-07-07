<?php
use CaveTrip\Core\View;
?>
<div class="page-header no-print">
    <div>
        <p class="eyebrow">Final Waiver</p>
        <h1><?= View::e($waiver['trip_title']) ?></h1>
        <p><?= View::e($waiver['trip_number']) ?> · finalized <?= View::e((string)$waiver['finalized_at']) ?></p>
    </div>
    <div class="button-row"><button class="button secondary" onclick="window.print()">Print</button></div>
</div>
<section class="panel waiver-document">
    <?= $waiver['final_html'] ?>
</section>
