<?php

declare(strict_types=1);

namespace CaveTrip\Services;

final class TripNumberService
{
    public static function generate(string $grottoSlug, int $sequence, ?int $year = null): string
    {
        $year ??= (int)date('Y');
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $grottoSlug) ?: 'CTM');
        return sprintf('%s-%d-%05d', $prefix, $year, $sequence);
    }
}
