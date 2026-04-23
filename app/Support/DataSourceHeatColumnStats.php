<?php

namespace App\Support;

/**
 * Min / max / median for data-source heat, keyed by exact CSV header names (matches {@see DataSourceCellHeat}).
 */
final class DataSourceHeatColumnStats
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<string|null>>  $rows
     * @param  array<string, mixed>  $rules  heat_rules: only columns with enabled rules contribute
     * @param  int|null  $paColumnIndex  display-order column index; when set with {@see $paMinimum}, only rows with PA >= minimum contribute to min/max/median
     * @return array<string, array{min: float, max: float, median: float}>
     */
    public static function compute(
        array $headers,
        array $rows,
        array $rules,
        ?int $paColumnIndex = null,
        ?float $paMinimum = null,
    ): array {
        $indexes = [];
        foreach ($rules as $name => $rule) {
            if (! is_array($rule) || ! ($rule['enabled'] ?? false)) {
                continue;
            }
            foreach ($headers as $i => $h) {
                if ((string) $h === (string) $name) {
                    $indexes[(string) $name] = (int) $i;

                    break;
                }
            }
        }

        if ($indexes === []) {
            return [];
        }

        /** @var array<string, list<float>> $valueLists */
        $valueLists = [];
        foreach (array_keys($indexes) as $name) {
            $valueLists[$name] = [];
        }

        $usePaGate = $paColumnIndex !== null && $paMinimum !== null
            && $paColumnIndex >= 0 && $paColumnIndex < count($headers);

        foreach ($rows as $row) {
            if ($usePaGate) {
                $paRaw = (string) ($row[$paColumnIndex] ?? '');
                $paVal = self::parseNumeric($paRaw);
                if ($paVal === null || $paVal < $paMinimum) {
                    continue;
                }
            }
            foreach ($indexes as $name => $idx) {
                $raw = (string) ($row[$idx] ?? '');
                $val = self::parseNumeric($raw);
                if ($val === null) {
                    continue;
                }
                $valueLists[$name][] = (float) $val;
            }
        }

        $stats = [];
        foreach ($indexes as $name => $_) {
            $vals = $valueLists[$name];
            if ($vals === []) {
                continue;
            }
            sort($vals, SORT_NUMERIC);
            $n = count($vals);
            $minF = (float) $vals[0];
            $maxF = (float) $vals[$n - 1];
            if (abs($maxF - $minF) < 1.0e-6) {
                continue;
            }
            if ($n % 2 === 1) {
                $median = (float) $vals[intdiv($n, 2)];
            } else {
                $mid = intdiv($n, 2);
                $median = ((float) $vals[$mid - 1] + (float) $vals[$mid]) / 2;
            }
            $stats[$name] = [
                'min' => $minF,
                'max' => $maxF,
                'median' => $median,
            ];
        }

        return $stats;
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
