<?php

declare(strict_types=1);

namespace CaveTrip\Controllers;

use CaveTrip\Core\Application;
use CaveTrip\Core\Http;
use CaveTrip\Core\Session;
use CaveTrip\Core\View;
use CaveTrip\Services\AuditLogService;
use CaveTrip\Services\AuthService;
use CaveTrip\Services\GrottoService;

final class GrottoSettingsController
{
    public function edit(Application $app): string
    {
        $user = (new AuthService($app->db()))->requireRole(['super_admin', 'grotto_admin']);
        $service = new GrottoService($app->db());
        $grotto = $service->findForUser($user);

        return View::render($app, 'settings/grotto', [
            'title' => 'Grotto Settings',
            'user' => $user,
            'grotto' => $grotto,
        ]);
    }

    public function update(Application $app): string
    {
        Http::requirePostCsrf();
        $user = (new AuthService($app->db()))->requireRole(['super_admin', 'grotto_admin']);
        $service = new GrottoService($app->db());
        $grotto = $service->findForUser($user);

        if ($grotto === null) {
            Session::flash('error', 'No grotto exists yet. Create the first grotto using tools/create_admin.php with --grotto-name.');
            return Http::redirect('/admin/grotto/settings');
        }

        $logoFilePath = (string)($grotto['logo_file_path'] ?? '');
        try {
            $uploadedPath = $this->handleLogoUpload($app);
            if ($uploadedPath !== null) {
                $logoFilePath = $uploadedPath;
            }

            $service->update((int)$grotto['id'], [
                'name' => (string)($_POST['name'] ?? ''),
                'slug' => (string)($_POST['slug'] ?? ''),
                'email' => (string)($_POST['email'] ?? ''),
                'phone' => (string)($_POST['phone'] ?? ''),
                'website_url' => (string)($_POST['website_url'] ?? ''),
                'mailing_address' => (string)($_POST['mailing_address'] ?? ''),
                'contact_name' => (string)($_POST['contact_name'] ?? ''),
                'logo_url' => (string)($_POST['logo_url'] ?? ''),
                'logo_file_path' => $logoFilePath,
            ]);

            (new AuditLogService($app->db()))->record(
                (int)$grotto['id'],
                (int)$user['id'],
                'grotto_settings.updated',
                'grotto',
                (int)$grotto['id'],
                ['name' => (string)($_POST['name'] ?? '')]
            );

            Session::flash('success', 'Grotto settings updated.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Unable to update grotto settings: ' . $e->getMessage());
        }

        return Http::redirect('/admin/grotto/settings');
    }

    private function handleLogoUpload(Application $app): ?string
    {
        if (!isset($_FILES['logo_file']) || !is_array($_FILES['logo_file'])) {
            return null;
        }

        $file = $_FILES['logo_file'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Logo upload failed.');
        }

        $tmpName = (string)$file['tmp_name'];
        $originalName = (string)$file['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'];
        if (!in_array($extension, $allowed, true)) {
            throw new \RuntimeException('Logo must be PNG, JPG, GIF, SVG, or WEBP.');
        }

        $uploadDir = $app->rootPath('public/uploads/logos');
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new \RuntimeException('Unable to create logo upload directory.');
        }

        $filename = 'logo-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($tmpName, $destination)) {
            throw new \RuntimeException('Unable to save uploaded logo.');
        }

        return '/uploads/logos/' . $filename;
    }
}
