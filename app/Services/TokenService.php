<?php

declare(strict_types=1);

namespace CaveTrip\Services;

final class TokenService
{
    public static function make(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }
}
