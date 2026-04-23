<?php

namespace App\Support;

use App\Models\DataSourceUpload;
use Illuminate\Support\Facades\Storage;

/**
 * Builds per-player career totals from a yearly HS Perfect Game-style CSV.
 *
 * Count columns (when headers match) are summed; rate columns are recomputed from career totals.
 * AVG and SLG use summed AB when present; otherwise PA (sheets without an AB column).
 */
final class CareerPgStatsAggregator
{
    /**
     * @return array{headers: list<string>, rows: list<list<string>>, row_count: int, player_column_index: int}
     */
    public static function fromSourceUpload(DataSourceUpload $source): array
    {
        /** @var list<string> $headers */
        $headers = array_map(static fn ($h) => is_string($h) ? $h : '', $source->header_row ?? []);
        if ($headers === []) {
            return ['headers' => [], 'rows' => [], 'row_count' => 0, 'player_column_index' => 0];
        }

        $absolutePath = Storage::disk($source->disk)->path($source->path);
        if (! is_file($absolutePath)) {
            return ['headers' => $headers, 'rows' => [], 'row_count' => 0, 'player_column_index' => DataSourceCsvHeaders::playerColumnIndex($headers)];
        }

        $playerIdx = DataSourceCsvHeaders::playerColumnIndex($headers);
        $yearIdx = DataSourceCsvHeaders::yearColumnIndex($headers);
        $pitchIdx = DataSourceCsvHeaders::pitchColumnIndex($headers);

        $semantics = [];
        foreach ($headers as $i => $h) {
            $semantics[$i] = self::semanticForHeader((string) $h, (int) $i, $playerIdx, $yearIdx, $pitchIdx);
        }

        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            return ['headers' => $headers, 'rows' => [], 'row_count' => 0, 'player_column_index' => $playerIdx];
        }

        /** @var array<string, array{display: string, sums: array<string, int>}> $groups */
        $groups = [];

        try {
            $fileHeader = fgetcsv($handle);
            if ($fileHeader === false) {
                return ['headers' => $headers, 'rows' => [], 'row_count' => 0, 'player_column_index' => $playerIdx];
            }

            while (($row = fgetcsv($handle)) !== false) {
                if (self::isBlankRow($row)) {
                    continue;
                }
                if ($pitchIdx !== null && ! self::isOverallPitchRow($pitchIdx, $row)) {
                    continue;
                }
                $rawPlayer = trim((string) ($row[$playerIdx] ?? ''));
                if ($rawPlayer === '') {
                    continue;
                }
                $gKey = strtolower(preg_replace('/\s+/', ' ', $rawPlayer));
                if (! isset($groups[$gKey])) {
                    $groups[$gKey] = [
                        'display' => $rawPlayer,
                        'sums' => self::emptySums(),
                    ];
                }
                self::accumulateRow($row, $semantics, $groups[$gKey]['sums']);
            }
        } finally {
            fclose($handle);
        }

        ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);

        $rows = [];
        foreach ($groups as $group) {
            $rows[] = self::buildOutputRow($headers, $semantics, $group['display'], $group['sums']);
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'row_count' => count($rows),
            'player_column_index' => $playerIdx,
        ];
    }

    /**
     * HS profile Perfect Game table: one career row (same slug keys as multi-year {@see HsRangerTraitsSheetResolver::extractSlugRow}).
     *
     * @param  list<list<string|null>>  $playerRows  All rows for this player (e.g. sorted by year desc)
     * @param  list<string>  $headers
     * @return array{row: array<string, string>, heat_raw_by_slug: array<string, string>}|null
     */
    public static function profileCircuitPgCareerWithHeatRaw(
        array $playerRows,
        array $headers,
        ?int $pitchCol,
    ): ?array {
        $headers = array_map(static fn ($h) => is_string($h) ? $h : '', $headers);
        if ($headers === [] || $playerRows === []) {
            return null;
        }

        $playerIdx = DataSourceCsvHeaders::playerColumnIndex($headers);
        $yearIdx = DataSourceCsvHeaders::yearColumnIndex($headers);
        $pitchIdx = $pitchCol;

        $semantics = [];
        foreach ($headers as $i => $h) {
            $semantics[$i] = self::semanticForHeader((string) $h, (int) $i, $playerIdx, $yearIdx, $pitchIdx);
        }

        $sums = self::emptySums();
        foreach ($playerRows as $row) {
            if ($pitchIdx !== null && ! self::isOverallPitchRow($pitchIdx, $row)) {
                continue;
            }
            self::accumulateRow($row, $semantics, $sums);
        }

        if ($sums['pa'] <= 0) {
            return null;
        }

        $pa = max(0, $sums['pa']);
        $ab = max(0, $sums['ab']);
        $bb = max(0, $sums['bb']);
        $k = max(0, $sums['k']);
        $b1 = max(0, $sums['b1']);
        $b2 = max(0, $sums['b2']);
        $b3 = max(0, $sums['b3']);
        $hr = max(0, $sums['hr']);
        $hits = $b1 + $b2 + $b3 + $hr;
        $tb = $b1 + (2 * $b2) + (3 * $b3) + (4 * $hr);

        $avgSlgDenom = $ab > 0 ? $ab : ($pa > 0 ? $pa : 0);
        $avg = $avgSlgDenom > 0 ? $hits / $avgSlgDenom : null;
        $slg = $avgSlgDenom > 0 ? $tb / $avgSlgDenom : null;
        $obp = $pa > 0 ? ($hits + $bb) / $pa : null;
        $bbPct = $pa > 0 ? $bb / $pa : null;
        $kPct = $pa > 0 ? $k / $pa : null;
        $ops = ($slg !== null && $obp !== null) ? $slg + $obp : null;
        $iso = ($slg !== null && $avg !== null) ? $slg - $avg : null;

        $row = [
            'year' => 'Career',
            'pa' => (string) $sums['pa'],
            'ops' => self::fmtSlashLineStat($ops),
            'avg' => self::fmtSlashLineStat($avg),
            'obp' => self::fmtSlashLineStat($obp),
            'slg' => self::fmtSlashLineStat($slg),
            'iso' => self::fmtSlashLineStat($iso),
            'bb_pct' => self::fmtPercentFromDecimal($bbPct),
            'k_pct' => self::fmtPercentFromDecimal($kPct),
        ];

        $heatRaw = [];
        if ($bbPct !== null) {
            $heatRaw['bb_pct'] = sprintf('%.3f', $bbPct);
        }
        if ($kPct !== null) {
            $heatRaw['k_pct'] = sprintf('%.3f', $kPct);
        }

        return ['row' => $row, 'heat_raw_by_slug' => $heatRaw];
    }

    /**
     * @return array<string, int>
     */
    private static function emptySums(): array
    {
        return [
            'gp' => 0,
            'pa' => 0,
            'ab' => 0,
            'b1' => 0,
            'b2' => 0,
            'b3' => 0,
            'hr' => 0,
            'bb' => 0,
            'k' => 0,
        ];
    }

    /**
     * @param  list<string|null>  $row
     * @param  array<int, string>  $semantics
     * @param  array<string, int>  $sums
     */
    private static function accumulateRow(array $row, array $semantics, array &$sums): void
    {
        foreach ($semantics as $i => $sem) {
            $key = match ($sem) {
                'sum_gp' => 'gp',
                'sum_pa' => 'pa',
                'sum_ab' => 'ab',
                'sum_1b' => 'b1',
                'sum_2b' => 'b2',
                'sum_3b' => 'b3',
                'sum_hr' => 'hr',
                'sum_bb' => 'bb',
                'sum_k' => 'k',
                default => null,
            };
            if ($key === null) {
                continue;
            }
            $cell = (string) ($row[$i] ?? '');
            $n = self::parseIntish($cell);
            if ($n === null) {
                continue;
            }
            $sums[$key] += $n;
        }
    }

    /**
     * @param  list<string>  $headers
     * @param  array<int, string>  $semantics
     * @param  array<string, int>  $sums
     * @return list<string>
     */
    private static function buildOutputRow(array $headers, array $semantics, string $displayPlayer, array $sums): array
    {
        $pa = max(0, $sums['pa']);
        $ab = max(0, $sums['ab']);
        $bb = max(0, $sums['bb']);
        $k = max(0, $sums['k']);
        $b1 = max(0, $sums['b1']);
        $b2 = max(0, $sums['b2']);
        $b3 = max(0, $sums['b3']);
        $hr = max(0, $sums['hr']);
        $hits = $b1 + $b2 + $b3 + $hr;
        $tb = $b1 + (2 * $b2) + (3 * $b3) + (4 * $hr);

        $avgSlgDenom = $ab > 0 ? $ab : ($pa > 0 ? $pa : 0);
        $avg = $avgSlgDenom > 0 ? $hits / $avgSlgDenom : null;
        $slg = $avgSlgDenom > 0 ? $tb / $avgSlgDenom : null;
        $obp = $pa > 0 ? ($hits + $bb) / $pa : null;
        $bbPct = $pa > 0 ? $bb / $pa : null;
        $kPct = $pa > 0 ? $k / $pa : null;
        $ops = ($slg !== null && $obp !== null) ? $slg + $obp : null;
        $iso = ($slg !== null && $avg !== null) ? $slg - $avg : null;

        $out = [];
        foreach ($headers as $i => $_h) {
            $sem = $semantics[$i] ?? 'other';
            $out[] = match ($sem) {
                '_player' => $displayPlayer,
                '_year' => 'Career',
                '_pitch' => '',
                'sum_gp' => (string) $sums['gp'],
                'sum_pa' => (string) $sums['pa'],
                'sum_ab' => (string) $sums['ab'],
                'sum_1b' => (string) $sums['b1'],
                'sum_2b' => (string) $sums['b2'],
                'sum_3b' => (string) $sums['b3'],
                'sum_hr' => (string) $sums['hr'],
                'sum_bb' => (string) $sums['bb'],
                'sum_k' => (string) $sums['k'],
                'out_bb_pct' => self::fmtPercentFromDecimal($bbPct),
                'out_k_pct' => self::fmtPercentFromDecimal($kPct),
                'out_avg' => self::fmtSlashLineStat($avg),
                'out_slg' => self::fmtSlashLineStat($slg),
                'out_obp' => self::fmtSlashLineStat($obp),
                'out_ops' => self::fmtSlashLineStat($ops),
                'out_iso' => self::fmtSlashLineStat($iso),
                default => '',
            };
        }

        return $out;
    }

    private static function semanticForHeader(
        string $header,
        int $i,
        int $playerIdx,
        ?int $yearIdx,
        ?int $pitchIdx,
    ): string {
        if ($i === $playerIdx) {
            return '_player';
        }
        if ($yearIdx !== null && $i === $yearIdx) {
            return '_year';
        }
        if ($pitchIdx !== null && $i === $pitchIdx) {
            return '_pitch';
        }

        $norm = DataSourceCsvHeaders::normalizeForMatch($header);
        $slug = DataSourceCsvHeaders::slugify($header);

        if (str_contains($norm, '%')) {
            if (self::isBbPercentHeader($norm, $slug)) {
                return 'out_bb_pct';
            }
            if (self::isKPercentHeader($norm, $slug)) {
                return 'out_k_pct';
            }
        }

        if (in_array($slug, ['bbpct', 'bbpercent', 'walkrate'], true)
            || ($slug === 'bb' && (str_contains($norm, 'pct') || str_contains($norm, 'percent')))) {
            return 'out_bb_pct';
        }
        if (in_array($slug, ['kpct', 'kpercent', 'strikeoutrate', 'krate'], true)) {
            return 'out_k_pct';
        }

        if (in_array($slug, ['avg', 'avgg', 'battingaverage'], true)) {
            return 'out_avg';
        }
        if ($slug === 'obp') {
            return 'out_obp';
        }
        if ($slug === 'slg') {
            return 'out_slg';
        }
        if ($slug === 'ops') {
            return 'out_ops';
        }
        if (in_array($slug, ['iso', 'isolatedpower', 'isop'], true)) {
            return 'out_iso';
        }

        if (self::isSumGp($norm, $slug)) {
            return 'sum_gp';
        }
        if (self::isSumPa($norm, $slug)) {
            return 'sum_pa';
        }
        if (self::isSumAb($norm, $slug)) {
            return 'sum_ab';
        }
        if (self::isSum1b($norm, $slug)) {
            return 'sum_1b';
        }
        if (self::isSum2b($norm, $slug)) {
            return 'sum_2b';
        }
        if (self::isSum3b($norm, $slug)) {
            return 'sum_3b';
        }
        if (self::isSumHr($norm, $slug)) {
            return 'sum_hr';
        }
        if (self::isSumBbWalks($norm, $slug)) {
            return 'sum_bb';
        }
        if (self::isSumK($norm, $slug)) {
            return 'sum_k';
        }

        return 'other';
    }

    private static function isBbPercentHeader(string $norm, string $slug): bool
    {
        if (str_contains($norm, 'walk') && str_contains($norm, '%')) {
            return true;
        }

        return str_contains($norm, 'bb') && str_contains($norm, '%') && ! str_contains($norm, 'bip');
    }

    private static function isKPercentHeader(string $norm, string $slug): bool
    {
        if (str_contains($norm, 'strike') && str_contains($norm, '%')) {
            return true;
        }

        return str_contains($norm, 'k') && str_contains($norm, '%') && ! str_contains($norm, 'bb');
    }

    private static function isSumGp(string $norm, string $slug): bool
    {
        return $slug === 'gp'
            || $slug === 'g'
            || $norm === 'games'
            || $norm === 'game'
            || $norm === 'gp';
    }

    private static function isSumPa(string $norm, string $slug): bool
    {
        return $slug === 'pa'
            || str_contains($norm, 'plate appearance')
            || $norm === 'plateappearances';
    }

    private static function isSumAb(string $norm, string $slug): bool
    {
        return $slug === 'ab'
            || str_contains($norm, 'at bat')
            || $norm === 'atbats'
            || $norm === 'atbat';
    }

    private static function isSum1b(string $norm, string $slug): bool
    {
        return $slug === '1b'
            || $norm === '1b'
            || $norm === 'singles'
            || $norm === 'single';
    }

    private static function isSum2b(string $norm, string $slug): bool
    {
        return $slug === '2b'
            || $norm === '2b'
            || $norm === 'doubles'
            || $norm === 'double';
    }

    private static function isSum3b(string $norm, string $slug): bool
    {
        return $slug === '3b'
            || $norm === '3b'
            || $norm === 'triples'
            || $norm === 'triple';
    }

    private static function isSumHr(string $norm, string $slug): bool
    {
        return $slug === 'hr'
            || str_contains($norm, 'home run')
            || $norm === 'homer'
            || $norm === 'homers';
    }

    private static function isSumBbWalks(string $norm, string $slug): bool
    {
        if ($slug === 'bb' || $norm === 'bb' || $norm === 'walks' || $norm === 'walk' || str_contains($norm, 'base on balls')) {
            return ! str_contains($norm, '%')
                && ! str_contains($norm, 'pct')
                && ! str_contains($norm, 'percent');
        }

        return $slug === 'uibb' || $norm === 'uibb';
    }

    private static function isSumK(string $norm, string $slug): bool
    {
        if (str_contains($norm, '%') || str_contains($norm, 'pct')) {
            return false;
        }

        return $slug === 'k'
            || $slug === 'so'
            || $norm === 'strikeout'
            || $norm === 'strikeouts'
            || $norm === 'punchouts';
    }

    /**
     * Decimal rate in (0–1) → display like 21.7%.
     */
    private static function fmtPercentFromDecimal(?float $x): string
    {
        if ($x === null) {
            return '';
        }

        return sprintf('%.1f%%', round($x * 100, 4));
    }

    /**
     * Three decimal places, omitting the leading zero before the decimal (e.g. .300 not 0.300).
     */
    private static function fmtSlashLineStat(?float $x): string
    {
        if ($x === null) {
            return '';
        }

        $s = sprintf('%.3f', $x);
        if (str_starts_with($s, '0.')) {
            return substr($s, 1);
        }
        if (str_starts_with($s, '-0.')) {
            return '-.'.substr($s, 3);
        }

        return $s;
    }

    private static function parseIntish(string $raw): ?int
    {
        $t = str_replace([',', ' '], '', trim($raw));
        if ($t === '' || ! is_numeric($t)) {
            return null;
        }

        return (int) round((float) $t);
    }

    /**
     * @param  list<string|null>|null  $row
     */
    private static function isBlankRow(?array $row): bool
    {
        if ($row === null || $row === []) {
            return true;
        }
        foreach ($row as $c) {
            if (trim((string) $c) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string|null>  $row
     */
    private static function isOverallPitchRow(int $pitchCol, array $row): bool
    {
        $v = strtoupper(trim((string) ($row[$pitchCol] ?? '')));

        return $v === '' || self::isAggregatePitchLabelToken($v);
    }

    private static function isAggregatePitchLabelToken(string $upper): bool
    {
        static $tokens = [
            'ALL', 'TOTAL', 'OVERALL', 'COMBINED', 'SEASON', 'TTL', 'SUM', 'AGG', 'GENERAL',
            'YEAR', 'FULL', 'TOT',
        ];

        return in_array($upper, $tokens, true);
    }
}
