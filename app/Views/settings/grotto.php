<section class="page-header">
    <div>
        <h1>Grotto Settings</h1>
        <p>Manage the grotto profile, contact information, and logo used throughout waivers and trip notices.</p>
    </div>
</section>

<?php if ($grotto === null): ?>
    <div class="alert error">
        No grotto exists yet. Create the first admin with <code>tools/create_admin.php --grotto-name="Six Ridges Grotto" --grotto-slug=six-ridges</code>.
    </div>
<?php else: ?>
    <form class="card form-stack" method="post" action="/admin/grotto/settings" enctype="multipart/form-data">
        <?= \CaveTrip\Core\Csrf::field() ?>

        <div class="form-grid">
            <label>
                Grotto Name
                <input type="text" name="name" value="<?= e($grotto['name'] ?? '') ?>" required>
            </label>

            <label>
                Slug
                <input type="text" name="slug" value="<?= e($grotto['slug'] ?? '') ?>" required>
            </label>

            <label>
                Email
                <input type="email" name="email" value="<?= e($grotto['email'] ?? '') ?>">
            </label>

            <label>
                Phone
                <input type="text" name="phone" value="<?= e($grotto['phone'] ?? '') ?>">
            </label>

            <label>
                Website URL
                <input type="url" name="website_url" value="<?= e($grotto['website_url'] ?? '') ?>">
            </label>

            <label>
                Contact Name
                <input type="text" name="contact_name" value="<?= e($grotto['contact_name'] ?? '') ?>">
            </label>
        </div>

        <label>
            Mailing Address
            <textarea name="mailing_address" rows="3"><?= e($grotto['mailing_address'] ?? '') ?></textarea>
        </label>

        <div class="form-grid">
            <label>
                Logo URL
                <input type="url" name="logo_url" value="<?= e($grotto['logo_url'] ?? '') ?>" placeholder="https://example.org/logo.png">
            </label>

            <label>
                Upload Logo
                <input type="file" name="logo_file" accept=".png,.jpg,.jpeg,.gif,.svg,.webp">
            </label>
        </div>

        <?php $logo = ($grotto['logo_file_path'] ?? '') ?: ($grotto['logo_url'] ?? ''); ?>
        <?php if ($logo): ?>
            <div class="logo-preview">
                <span>Current Logo</span>
                <img src="<?= e($logo) ?>" alt="Grotto logo preview">
            </div>
        <?php endif; ?>

        <div class="button-row">
            <button type="submit" class="button">Save Settings</button>
            <a class="button secondary" href="/dashboard">Cancel</a>
        </div>
    </form>
<?php endif; ?>
