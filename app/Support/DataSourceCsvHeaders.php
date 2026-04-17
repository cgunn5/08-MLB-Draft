<?php

namespace App\Support;

final class DataSourceCsvHeaders
{
    public static function normalizeForMatch(string $header): string
    {
        $t = (string) preg_replace('/^\xEF\xBB\xBF|\x{FEFF}/u', '', trim($header));

        return strtolower($t);
    }

    /**
     * Alphanumeric slug for fuzzy column matching (xwOBAcon → xwobacon).
     */
    public static function slugify(string $header): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', self::normalizeForMatch($header)));
    }

    /**
     * @param  list<string>  $headerRow
     */
    public static function playerColumnIndex(array $headerRow): int
    {
        /** @var list<int> $candidates */
        $candidates = [];
        foreach ($headerRow as $i => $h) {
            $norm = self::normalizeForMatch((string) $h);
            if ($norm === 'player' || str_contains($norm, 'player')) {
                $candidates[] = (int) $i;

                continue;
            }
            if ($norm === 'name' || str_ends_with($norm, ' name')) {
                $candidates[] = (int) $i;
            }
        }

        if ($candidates === []) {
            return 0;
        }

        foreach ($candidates as $i) {
            $norm = self::normalizeForMatch((string) ($headerRow[$i] ?? ''));
            if ($norm === 'player') {
                return $i;
            }
        }

        return $candidates[0];
    }

    /**
     * @param  list<string>  $headerRow
     */
    public static function yearColumnIndex(array $headerRow): ?int
    {
        foreach ($headerRow as $i => $h) {
            $norm = self::normalizeForMatch((string) $h);
            if ($norm === 'year' || $norm === 'season' || $norm === 'yr') {
                return (int) $i;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $headerRow
     */
    public static function pitchColumnIndex(array $headerRow): ?int
    {
        foreach ($headerRow as $i => $h) {
            $norm = self::normalizeForMatch((string) $h);
            $slug = self::slugify((string) $h);
            if ($norm === 'pitch' || $norm === 'pitch type' || $norm === 'pitchtype' || $slug === 'ptype') {
                return (int) $i;
            }
        }

        return null;
    }
}
