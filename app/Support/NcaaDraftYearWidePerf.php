<?php

namespace App\Support;

/**
 * NCAA "Overall" CSVs: one row per player with Draft Year = N and stat columns
 * for N (plain headers), N-1 ("… (N-1)"), and N-2 ("… (N-2)").
 */
final class NcaaDraftYearWidePerf
{
    /**
     * True when the sheet has a draft-year column and at least one (N-1) or (N-2) stat header.
     *
     * @param  list<string>  $headers
     */
    public static function usesWideLayout(array $headers): bool
    {
        if (DataSourceCsvHeaders::draftYearColumnIndex($headers) === null) {
            return false;
        }
        foreach ($headers as $h) {
            if (! is_string($h)) {
                continue;
            }
            if (preg_match('/\(\s*N\s*-\s*[12]\s*\)/i', $h)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<string>  $blockSlugs  e.g. pa, avg for perf or k_pct, bb_pct for K-zone wide columns
     * @return array<int, array<string, int>> tier 0 = N, 1 = N-1, 2 = N-2 → canonical slug → column index
     */
    public static function tierSlugColumnMap(array $headers, array $blockSlugs): array
    {
        $slugSet = array_fill_keys($blockSlugs, true);
        /** @var array<int, array<string, int>> $out */
        $out = [0 => [], 1 => [], 2 => []];
        foreach ($headers as $i => $h) {
            if (! is_string($h) || trim($h) === '') {
                continue;
            }
            $tier = self::headerTier($h);
            $canonical = self::headerToBlockSlug($h, $slugSet);
            if ($canonical === null) {
                continue;
            }
            if (! isset($out[$tier][$canonical])) {
                $out[$tier][$canonical] = (int) $i;
            }
        }

        return $out;
    }

    public static function parseDraftYearN(string $cell): ?int
    {
        $t = trim($cell);
        if ($t === '') {
            return null;
        }
        if (preg_match('/\b(19|20)\d{2}\b/', $t, $m)) {
            return (int) $m[0];
        }

        return null;
    }

    private static function headerTier(string $h): int
    {
        if (preg_match('/\(\s*N\s*-\s*2\s*\)/i', $h)) {
            return 2;
        }
        if (preg_match('/\(\s*N\s*-\s*1\s*\)/i', $h)) {
            return 1;
        }

        return 0;
    }

    /**
     * @param  array<string, true>  $slugSet
     */
    private static function headerToBlockSlug(string $header, array $slugSet): ?string
    {
        $base = trim((string) preg_replace('/\s*\(\s*N\s*-\s*[12]\s*\)\s*$/i', '', trim($header)));
        $canonical = self::resolveCanonicalSlugForBlock($base, $slugSet);

        return ($canonical !== null && isset($slugSet[$canonical])) ? $canonical : null;
    }

    /**
     * @param  array<string, true>  $slugSet  allowed canonical slugs for the active NCAA block
     */
    private static function resolveCanonicalSlugForBlock(string $baseHeader, array $slugSet): ?string
    {
        $slugKey = DataSourceCsvHeaders::slugify($baseHeader);
        if ($slugKey === '') {
            return null;
        }
        $aliases = NcaaRangerTraitsSheetLayout::slugAliases();
        foreach ($aliases as $canonical => $aliasList) {
            if (! isset($slugSet[$canonical])) {
                continue;
            }
            foreach ($aliasList as $alias) {
                $key = DataSourceCsvHeaders::aliasSlug($alias);
                if ($key !== '' && $slugKey === $key) {
                    return $canonical;
                }
            }
        }
        foreach (array_keys($slugSet) as $s) {
            $key = strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $s));
            if ($key !== '' && $slugKey === $key) {
                return $s;
            }
        }

        return null;
    }
}
