<?php
use CaveTrip\Core\Csrf;
use CaveTrip\Core\View;
$isEdit=is_array($cave); $value=static fn(string $key):string=>View::e((string)($cave[$key]??'')); $selectedLandownerId=(int)($cave['landowner_id']??0); $checked=!$isEdit||(int)($cave['active']??1)===1?'checked':'';
?>
<div class="page-header"><div><h1><?= $isEdit?'Edit Cave':'Add Cave' ?></h1><p>Store access information without precise GPS coordinates.</p></div><a class="button secondary" href="/caves">Back to Caves</a></div>
<form method="post" action="<?= View::e($action) ?>" class="panel form-stack">
<?= Csrf::field() ?>
<label>Cave Name<input type="text" name="name" required value="<?= $value('name') ?>"></label>
<label>State<input type="text" name="state" value="<?= $value('state') ?>" placeholder="Tennessee"></label>
<label>County<input type="text" name="county" value="<?= $value('county') ?>"></label>
<label>Landowner<select name="landowner_id"><option value="">No landowner selected</option><?php foreach($landowners as $landowner): ?><option value="<?= (int)$landowner['id'] ?>" <?= (int)$landowner['id']===$selectedLandownerId?'selected':'' ?>><?= View::e((string)$landowner['name']) ?></option><?php endforeach; ?></select></label>
<label>Access Directions<textarea name="access_directions" rows="4"><?= $value('access_directions') ?></textarea></label>
<label>Parking Notes<textarea name="parking_notes" rows="3"><?= $value('parking_notes') ?></textarea></label>
<label>Access Notes<textarea name="access_notes" rows="4"><?= $value('access_notes') ?></textarea></label>
<label>Gate / Lock Code<input type="text" name="gate_code" value="<?= $value('gate_code') ?>"><small class="text-muted">Operational access information. Do not enter GPS coordinates here.</small></label>
<label>Sensitive Notes<textarea name="sensitive_notes" rows="5"><?= $value('sensitive_notes') ?></textarea></label>
<label class="checkbox-row"><input type="checkbox" name="active" value="1" <?= $checked ?>> Active</label>
<div class="form-actions"><button type="submit" class="button"><?= $isEdit?'Save Cave':'Create Cave' ?></button></div>
</form>
