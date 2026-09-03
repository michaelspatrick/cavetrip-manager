<?php use CaveTrip\Core\Csrf; use CaveTrip\Core\View; $s=$settings??[]; $provider=(string)($s['provider']??'ses'); ?>
<div class="page-header"><div><h1>Email Settings</h1><p>Configure outgoing mail for trip invitations, waiver notices, reminders, and system messages.</p></div></div>

<form class="card form-stack" method="post" action="/admin/email/settings">
    <?= Csrf::field() ?>
    <h2>Outgoing Email</h2>
    <div class="form-grid email-grid">
        <label>Provider
            <select name="provider" id="mail-provider">
                <option value="ses" <?= $provider==='ses'?'selected':'' ?>>Amazon SES (SMTP)</option>
                <option value="smtp" <?= $provider==='smtp'?'selected':'' ?>>Custom SMTP Server</option>
            </select>
        </label>
        <label>From Name<input name="from_name" required value="<?= View::e($s['from_name']??($grotto['name']??'CaveTrip Manager')) ?>"></label>
        <label>From Email<input type="email" name="from_email" required value="<?= View::e($s['from_email']??($grotto['email']??'')) ?>"></label>
        <label>Reply-To Email<input type="email" name="reply_to_email" value="<?= View::e($s['reply_to_email']??($grotto['email']??'')) ?>"></label>
    </div>

    <div id="ses-fields" class="provider-box">
        <h3>Amazon SES</h3>
        <p class="muted">Uses Amazon SES SMTP credentials. Your SES From address/domain must be verified, and sandbox accounts can only send to verified recipients.</p>
        <label>AWS Region<input name="ses_region" value="<?= View::e($s['ses_region']??'us-east-1') ?>" placeholder="us-east-1"></label>
        <p class="help-text">SMTP host is generated automatically as <code>email-smtp.&lt;region&gt;.amazonaws.com</code>.</p>
    </div>

    <div id="smtp-fields" class="provider-box">
        <h3>SMTP Connection</h3>
        <div class="form-grid email-grid">
            <label>SMTP Host<input name="smtp_host" value="<?= View::e($s['smtp_host']??'') ?>" placeholder="smtp.example.com"></label>
            <label>Port<input type="number" min="1" max="65535" name="smtp_port" value="<?= (int)($s['smtp_port']??587) ?>"></label>
            <label>Encryption
                <select name="smtp_encryption">
                    <?php foreach(['tls'=>'STARTTLS / TLS','ssl'=>'Implicit SSL/TLS','none'=>'None'] as $v=>$label): ?><option value="<?= $v ?>" <?= (($s['smtp_encryption']??'tls')===$v)?'selected':'' ?>><?= $label ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>SMTP Username<input name="smtp_username" autocomplete="off" value="<?= View::e($s['smtp_username']??'') ?>"></label>
            <label>SMTP Password<input type="password" name="smtp_password" autocomplete="new-password" placeholder="<?= !empty($s['smtp_password_encrypted'])?'Leave blank to keep current password':'Enter SMTP password' ?>"></label>
        </div>
        <p class="help-text">Credentials are encrypted at rest with a server-local key in <code>storage/keys/mail.key</code>. Keep that key with your application backups.</p>
    </div>

    <div class="button-row"><button class="button" type="submit">Save Email Settings</button></div>
</form>

<?php if($settings): ?>
<form class="card form-stack" method="post" action="/admin/email/settings/test">
    <?= Csrf::field() ?>
    <h2>Send Test Email</h2>
    <p>Save your settings first, then send a real message through the configured provider.</p>
    <label>Send Test To<input type="email" name="test_email" required value="<?= View::e($currentUser['email']??$grotto['email']??'') ?>"></label>
    <div class="button-row"><button class="button" type="submit">Send Test Email</button></div>
</form>
<?php endif; ?>

<script>
(function(){
 const provider=document.getElementById('mail-provider'), ses=document.getElementById('ses-fields'), smtp=document.getElementById('smtp-fields');
 function sync(){ const isSes=provider.value==='ses'; ses.style.display=isSes?'block':'none'; smtp.style.display='block'; const host=smtp.querySelector('[name="smtp_host"]'); if(isSes){host.closest('label').style.display='none';}else{host.closest('label').style.display='block';} }
 provider.addEventListener('change',sync); sync();
})();
</script>
