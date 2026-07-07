<?php

declare(strict_types=1);

namespace CaveTrip\Core;

final class Http
{
    public static function redirect(string $to): string
    {
        header('Location: ' . $to, true, 302);
        return '';
    }

    public static function requirePostCsrf(): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo 'Page expired. Please go back and try again.';
            exit;
        }
    }
}
