<?php

namespace App\Support;

use App\Models\DataSourceUpload;

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
        if ($t === 'δ' || $t === 'Δ') {
            return 'delta';
        }
        $t = str_replace('%', 'pct', $t);

        return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $t));
    }

    /** Normalized key for matching a canonical slug alias to a slugified CSV header. */
    public static function aliasSlug(string $alias): string
    {
        return self::slugify($alias);
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

        foreach ($headerRow as $i => $h) {
            $norm = trim((string) preg_replace('/\s+/u', ' ', self::normalizeForMatch((string) $h)));
            if ($norm === '') {
                continue;
            }
            if (str_contains($norm, 'game year')
                || str_contains($norm, 'stat year')
                || str_contains($norm, 'season year')
                || str_contains($norm, 'calendar year')
                || str_contains($norm, 'competition year')
                || str_contains($norm, 'school year')
                || str_contains($norm, 'academic year')) {
                return (int) $i;
            }
        }

        return null;
    }

    /**
     * Draft class year N for NCAA wide-format stat columns (plain = N, (N-1), (N-2)).
     *
     * @param  list<string>  $headerRow
     */
    public static function draftYearColumnIndex(array $headerRow): ?int
    {
        foreach ($headerRow as $i => $h) {
            $norm = self::normalizeForMatch((string) $h);
            $norm = trim((string) preg_replace('/\s+/u', ' ', $norm));
            if ($norm === 'draft year' || $norm === 'draft yr' || str_contains($norm, 'draft year')) {
                return (int) $i;
            }
        }

        return null;
    }

    /**
     * Best-effort portal for a recovered CSV (used when DB metadata was lost).
     *
     * @param  list<string>  $headerRow
     */
    public static function guessDatasetPortal(array $headerRow): string
    {
        if (self::draftYearColumnIndex($headerRow) !== null) {
            return DataSourceUpload::PORTAL_NCAA;
        }
        if (self::headerRowLooksLikeNcaaWideStats($headerRow)) {
            return DataSourceUpload::PORTAL_NCAA;
        }
        if (self::hsCompBucketColumnIndex($headerRow) !== null) {
            return DataSourceUpload::PORTAL_HS;
        }

        return DataSourceUpload::PORTAL_HS;
    }

    /**
     * NCAA library exports often include wOBA / xWOBA-style columns; HS Ranger Overall exports do not.
     *
     * @param  list<string>  $headerRow
     */
    private static function headerRowLooksLikeNcaaWideStats(array $headerRow): bool
    {
        foreach ($headerRow as $h) {
            $slug = self::slugify((string) $h);
            if ($slug !== '' && str_contains($slug, 'woba')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $headerRow
     */
    public static function pitchColumnIndex(array $headerRow): ?int
    {
        foreach ($headerRow as $i => $h) {
            $norm = trim((string) preg_replace('/\s+/u', ' ', self::normalizeForMatch((string) $h)));
            if ($norm === '') {
                continue;
            }
            $slug = self::slugify((string) $h);
            if ($norm === 'pitch'
                || $norm === 'pitch type'
                || $norm === 'pitchtype'
                || $slug === 'ptype'
                || (str_contains($norm, 'pitch') && str_contains($norm, 'type'))
                || (str_contains($norm, 'pitch') && str_contains($norm, 'group'))
                || str_contains($norm, 'pitch class')) {
                return (int) $i;
            }
        }

        foreach ($headerRow as $i => $h) {
            $norm = trim((string) preg_replace('/\s+/u', ' ', self::normalizeForMatch((string) $h)));
            if ($norm === 'type') {
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
     * Pitch-count / sample-size column for heat gating: short "P" first, then common full headers (e.g. NCAA "PITCHES").
     *
     * @param  list<string>  $headerRow
     */
    public static function pitchCountColumnIndex(array $headerRow): ?int
    {
        foreach ($headerRow as $i => $h) {
            $slug = self::slugify((string) $h);
            if ($slug === 'p') {
                return (int) $i;
            }
        }
        foreach ($headerRow as $i => $h) {
            $raw = (string) $h;
            $norm = trim((string) preg_replace('/\s+/u', ' ', self::normalizeForMatch($raw)));
            $slug = self::slugify($raw);
            if ($slug === 'pitches'
                || $slug === 'pitchcount'
                || $slug === 'pitchcounts'
                || $norm === 'pitch count'
                || $norm === 'pitch counts') {
                return (int) $i;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $headerRow
     */
    public static function columnIndexForHeaderName(array $headerRow, string $name): ?int
    {
        $want = trim($name);
        if ($want === '') {
            return null;
        }
        foreach ($headerRow as $i => $h) {
            if (trim((string) $h) === $want) {
                return (int) $i;
            }
        }
        $lw = strtolower($want);
        foreach ($headerRow as $i => $h) {
            if (strtolower(trim((string) $h)) === $lw) {
                return (int) $i;
            }
        }

        return null;
    }

    /**
     * Default browse/profile heat volume column when no explicit choice: P if present, else PA.
     *
     * @param  list<string>  $headerRow
     */
    public static function defaultHeatVolumeColumnIndex(array $headerRow): ?int
    {
        $p = self::pitchCountColumnIndex($headerRow);
        if ($p !== null) {
            return $p;
        }

        return self::plateAppearancesColumnIndex($headerRow);
    }

    /**
     * Heat volume column: optional `heat_volume_header` in browse settings, else {@see defaultHeatVolumeColumnIndex}.
     *
     * @param  list<string>  $headerRow
     * @param  array<string, mixed>|null  $browseSettings  dataset_browse_settings
     */
    public static function heatVolumeColumnIndex(array $headerRow, ?array $browseSettings): ?int
    {
        if (is_array($browseSettings) && array_key_exists('heat_volume_header', $browseSettings)) {
            $raw = $browseSettings['heat_volume_header'];
            if ($raw !== null && is_string($raw)) {
                $t = trim($raw);
                if ($t !== '' && strcasecmp($t, '__auto__') !== 0) {
                    if (strcasecmp($t, 'p') === 0) {
                        return self::pitchCountColumnIndex($headerRow);
                    }
                    if (strcasecmp($t, 'pa') === 0) {
                        return self::plateAppearancesColumnIndex($headerRow);
                    }
                    $idx = self::columnIndexForHeaderName($headerRow, $t);
                    if ($idx !== null) {
                        return $idx;
                    }
                }
            }
        }

        return self::defaultHeatVolumeColumnIndex($headerRow);
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
                || $norm === 'drnd'
                || $norm === 'drnds'
                || $norm === 'rounds'
                || $norm === 'round bucket'
                || $slug === 'rnds'
                || $slug === 'rounds'
                || $slug === 'drnd'
                || $slug === 'draftrnd'
                || str_contains($norm, 'comp round')
                || str_contains($norm, 'draft rnd')
                || str_contains($norm, 'draft round')) {
                return (int) $i;
            }
        }

        foreach ($headerRow as $i => $h) {
            $letters = strtolower((string) preg_replace('/[^a-z]/i', '', (string) $h));
            if ($letters === 'rnds' || $letters === 'drnd' || $letters === 'drnds') {
                return (int) $i;
            }
        }

        return null;
    }
}
