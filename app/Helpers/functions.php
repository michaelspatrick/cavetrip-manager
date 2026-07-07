<?php

declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path = ''): string
{
    $base = rtrim((string)env('APP_URL', ''), '/');
    $path = '/' . ltrim($path, '/');
    return $base !== '' ? $base . $path : $path;
}
