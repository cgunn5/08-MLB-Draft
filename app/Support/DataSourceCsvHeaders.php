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
     * Percent headers stay distinct from count headers (K% → kpct vs K → k; BB% → bbpct vs BB → bb).
     */
    public static function slugify(string $header): string
    {
        $t = self::normalizeForMatch($header);
        $t = str_replace('%', 'pct', $t);

        return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $t));
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
            if ($norm === 'pitch'
                || $norm === 'pitch type'
                || $norm === 'pitchtype'
                || $norm === 'type'
                || $slug === 'ptype') {
                return (int) $i;
            }
        }

        return null;
    }

    /**
     * Plate appearances column for heat / qualification (exact "PA" style headers).
     *
     * @param  list<string>  $headerRow
     */
    public static function plateAppearancesColumnIndex(array $headerRow): ?int
    {
        foreach ($headerRow as $i => $h) {
            $raw = (string) $h;
            $norm = self::normalizeForMatch($raw);
            $norm = (string) preg_replace('/[\x{00A0}\x{2007}\x{202F}\x{3000}]/u', ' ', $norm);
            $norm = trim((string) preg_replace('/\s+/u', ' ', $norm));
            $slug = self::slugify($raw);
            if ($norm === 'pa'
                || $norm === 'pas'
                || $norm === 'plate appearances'
                || $norm === 'plate appearance'
                || str_contains($norm, 'plate appearance')
                || $slug === 'pa'
                || $slug === 'pas') {
                return (int) $i;
            }
            $tokens = preg_split('/[^a-z0-9%]+/i', $norm) ?: [];
            foreach ($tokens as $tok) {
                $t = str_replace('%', 'pct', strtolower(trim((string) $tok)));
                if ($t === 'pa' || $t === 'pas') {
                    return (int) $i;
                }
            }
        }

        foreach ($headerRow as $i => $h) {
            $letters = strtolower((string) preg_replace('/[^a-z]/i', '', (string) $h));
            if ($letters === 'pa' || $letters === 'pas') {
                return (int) $i;
            }
        }

        return null;
    }

    /**
     * Draft comp / round bucket column (e.g. "Rnds" → 1-2, 3-6, 7+) for HS profile heat scoping.
     *
     * @param  list<string>  $headerRow
     */
    public static function hsCompBucketColumnIndex(array $headerRow): ?int
    {
        foreach ($headerRow as $i => $h) {
            $raw = (string) $h;
            $norm = self::normalizeForMatch($raw);
            $norm = (string) preg_replace('/[\x{00A0}\x{2007}\x{202F}\x{3000}]/u', ' ', $norm);
            $norm = trim((string) preg_replace('/\s+/u', ' ', $norm));
            $slug = self::slugify($raw);
            if ($norm === 'rnds'
                || $norm === 'rnd'
                || $norm === 'rounds'
                || $norm === 'round bucket'
                || $slug === 'rnds'
                || $slug === 'rounds'
                || str_contains($norm, 'comp round')) {
                return (int) $i;
            }
        }

        foreach ($headerRow as $i => $h) {
            $letters = strtolower((string) preg_replace('/[^a-z]/i', '', (string) $h));
            if ($letters === 'rnds') {
                return (int) $i;
            }
        }

        return null;
    }
}
