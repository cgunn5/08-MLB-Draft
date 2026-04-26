<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Empty / missing cell display for player trait sheets, header grades, and related UI.
 */
final class PlayerSheetPlaceholder
{
    public const string CELL = '-';

    public static function isEmptyDisplay(string $value): bool
    {
        $t = trim($value);

        return $t === ''
            || $t === self::CELL
            || $t === '—'
            || strtoupper($t) === '#N/A';
    }
}
