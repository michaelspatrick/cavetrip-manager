<?php

declare(strict_types=1);

namespace CaveTrip\Core;

final class View
{
    public static function render(Application $app, string $view, array $data = []): string
    {
        $viewFile = $app->rootPath('app/Views/' . $view . '.php');
        if (!is_file($viewFile)) {
            throw new \RuntimeException('View not found: ' . $view);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $publicViews = [
            'trips/signup',
            'signatures/sign',
        ];
        $layout = in_array($view, $publicViews, true) ? 'public' : 'app';

        ob_start();
        require $app->rootPath('app/Views/layouts/' . $layout . '.php');
        return (string)ob_get_clean();
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
