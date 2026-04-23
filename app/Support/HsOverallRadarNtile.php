<?php

namespace App\Support;

/**
 * Quintile (NTILE 5) positions for HS Overall radar vs a draft comp bucket.
 *
 * @phpstan-type AxisDef array{slug: string, label: string, invert: bool}
 * @phpstan-type AxisOut array{slug: string, label: string, invert: bool, ntile: int|null, chart_pct: float, raw: string}
 */
final class HsOverallRadarNtile
{
    /** @var list<AxisDef> */
    public const array AXES = [
        ['slug' => 'ops', 'label' => 'OPS', 'invert' => false],
        ['slug' => 'swm_pct', 'label' => 'SwM%', 'invert' => false],
        ['slug' => 'gb_pct', 'label' => 'GB%', 'invert' => false],
        ['slug' => 'ev95', 'label' => 'EV95', 'invert' => false],
        ['slug' => 'ch_pct', 'label' => 'CH%', 'invert' => true],
    ];

    /**
     * @param  list<string>  $headers
     * @param  list<list<string|null>>  $distributionRows  Comp-scoped population (same rules as heat scoping)
     * @param  array<string, int>  $slugToIdx  Canonical slug → column index
     * @param  array<string, string>  $playerSlugRow  Raw cells for player (from extractSlugRow)
     * @param  int|null  $paIdx  Plate appearances column
     * @param  float|null  $paMin  Minimum PA to include a row in distributions
     * @return array{axes: list<AxisOut>, values: list<float>, comp_scope: string|null}|null
     */
    public static function compute(
        array $headers,
        array $distributionRows,
        array $slugToIdx,
        array $playerSlugRow,
        ?int $paIdx,
        ?float $paMin,
        ?string $compScope,
    ): ?array {
        $axesOut = [];
        $values = [];
        $anyAxis = false;

        foreach (self::AXES as $def) {
            $slug = $def['slug'];
            $idx = $slugToIdx[$slug] ?? null;
            if ($idx === null || $idx < 0 || $idx >= count($headers)) {
                return null;
            }

            $population = self::collectNumericColumn(
                $distributionRows,
                $idx,
                $paIdx,
                $paMin,
            );

            $rawPlayer = trim((string) ($playerSlugRow[$slug] ?? ''));
            if ($rawPlayer === '' || $rawPlayer === '—') {
                $axesOut[] = [
                    'slug' => $slug,
                    'label' => $def['label'],
                    'invert' => $def['invert'],
                    'ntile' => null,
                    'chart_pct' => 0.0,
                    'raw' => '—',
                ];
                $values[] = 0.0;

                continue;
            }

            $playerVal = self::parseNumeric($rawPlayer);
            if ($playerVal === null) {
                $axesOut[] = [
                    'slug' => $slug,
                    'label' => $def['label'],
                    'invert' => $def['invert'],
                    'ntile' => null,
                    'chart_pct' => 0.0,
                    'raw' => $rawPlayer,
                ];
                $values[] = 0.0;

                continue;
            }

            $effectivePlayer = $def['invert'] ? -$playerVal : $playerVal;
            $effectivePop = [];
            foreach ($population as $v) {
                $effectivePop[] = $def['invert'] ? -$v : $v;
            }

            $n = count($effectivePop);
            if ($n < 2) {
                $axesOut[] = [
                    'slug' => $slug,
                    'label' => $def['label'],
                    'invert' => $def['invert'],
                    'ntile' => null,
                    'chart_pct' => 50.0,
                    'raw' => $rawPlayer,
                ];
                $values[] = 50.0;
                $anyAxis = true;

                continue;
            }

            sort($effectivePop, SORT_NUMERIC);
            $ntile = self::valueToQuintileNtile($effectivePlayer, $effectivePop);
            $chartPct = min(100.0, max(0.0, $ntile * 20.0));

            $axesOut[] = [
                'slug' => $slug,
                'label' => $def['label'],
                'invert' => $def['invert'],
                'ntile' => $ntile,
                'chart_pct' => $chartPct,
                'raw' => $rawPlayer,
            ];
            $values[] = $chartPct;
            $anyAxis = true;
        }

        if (! $anyAxis) {
            return null;
        }

        return [
            'axes' => $axesOut,
            'values' => $values,
            'comp_scope' => $compScope,
        ];
    }

    /**
     * NTILE(5) from mid-rank among the comp population (higher effective value = better).
     *
     * @param  list<float>  $sortedAsc
     */
    private static function valueToQuintileNtile(float $value, array $sortedAsc): int
    {
        $n = count($sortedAsc);
        if ($n === 0) {
            return 3;
        }

        $min = $sortedAsc[0];
        $max = $sortedAsc[$n - 1];
        $eps = 1.0e-9;

        if ($value < $min - $eps) {
            $rankMid = 0.5;
        } elseif ($value > $max + $eps) {
            $rankMid = (float) $n + 0.5;
        } else {
            $lt = 0;
            foreach ($sortedAsc as $x) {
                if ($x < $value - $eps) {
                    $lt++;
                }
            }
            $eq = 0;
            foreach ($sortedAsc as $x) {
                if (abs($x - $value) <= $eps) {
                    $eq++;
                }
            }
            if ($eq > 0) {
                $rankMid = $lt + ($eq + 1) / 2.0;
            } else {
                $rankMid = $lt + 0.5;
            }
        }

        $ntile = (int) ceil($rankMid / $n * 5);

        return min(5, max(1, $ntile));
    }

    /**
     * @param  list<list<string|null>>  $rows
     * @return list<float>
     */
    private static function collectNumericColumn(
        array $rows,
        int $colIdx,
        ?int $paIdx,
        ?float $paMin,
    ): array {
        $usePa = $paIdx !== null && $paMin !== null
            && $paIdx >= 0;

        $out = [];
        foreach ($rows as $row) {
            if ($usePa) {
                $paRaw = (string) ($row[$paIdx] ?? '');
                $paVal = self::parseNumeric($paRaw);
                if ($paVal === null || $paVal < $paMin) {
                    continue;
                }
            }
            $raw = (string) ($row[$colIdx] ?? '');
            $v = self::parseNumeric($raw);
            if ($v !== null) {
                $out[] = $v;
            }
        }

        return $out;
    }

    private static function parseNumeric(string $raw): ?float
    {
        $t = str_replace([',', '%', ' '], '', trim($raw));
        if ($t === '' || ! is_numeric($t)) {
            return null;
        }

        return (float) $t;
    }
}
