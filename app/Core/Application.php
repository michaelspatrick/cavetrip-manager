<?php

declare(strict_types=1);

namespace CaveTrip\Core;

use PDO;

final class Application
{
    private ?PDO $pdo = null;

    public function __construct(private readonly string $rootPath)
    {
    }

    public function rootPath(string $path = ''): string
    {
        return $this->rootPath . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return env($key, $default);
    }

    public function db(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $host = (string)$this->config('DB_HOST', '127.0.0.1');
        $port = (string)$this->config('DB_PORT', '3306');
        $database = (string)$this->config('DB_DATABASE', 'cavetrip_manager');
        $username = (string)$this->config('DB_USERNAME', 'root');
        $password = (string)$this->config('DB_PASSWORD', '');
        $charset = (string)$this->config('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

        $this->pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $this->pdo;
    }
}
