<?php

declare(strict_types=1);

namespace CaveTrip\Core;

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    public function dispatch(Application $app): void
    {
        Session::start();

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = $this->normalize($path);

        $handler = $this->routes[$method][$path] ?? null;
        if ($handler === null) {
            if (!isset($this->routes[$method])) {
                http_response_code(405);
                echo 'Method not allowed';
                return;
            }
            http_response_code(404);
            echo View::render($app, 'pages/404', ['title' => 'Page Not Found']);
            return;
        }

        echo $handler($app);
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
