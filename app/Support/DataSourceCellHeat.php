<?php

namespace App\Support;

/**
 * Server-side match for {@see resources/js/app.js} datasetCellStyle (data source grid heat).
 */
final class DataSourceCellHeat
{
    /**
     * @param  array<string, mixed>|null  $heatRules
     * @param  array<string, mixed>|null  $heatColumnStats
     */
    public static function inlineStyleFromRaw(
        string $raw,
        string $headerName,
        ?array $heatRules,
        ?array $heatColumnStats,
    ): ?string {
        if ($heatRules === null || $heatColumnStats === null || $heatRules === [] || $heatColumnStats === []) {
            return null;
        }

        $rule = $heatRules[$headerName] ?? null;
        $stats = $heatColumnStats[$headerName] ?? null;
        if (! is_array($rule) || ! ($rule['enabled'] ?? false) || ! is_array($stats)) {
            return null;
        }

        $min = $stats['min'] ?? null;
        $max = $stats['max'] ?? null;
        if ($min === null || $max === null) {
            return null;
        }

        $minF = (float) $min;
        $maxF = (float) $max;
        $eps = 1e-6;
        if (abs($maxF - $minF) < $eps) {
            return null;
        }

        $tRaw = str_replace([',', '%', ' '], '', trim($raw));
        if ($tRaw === '' || ! is_numeric($tRaw)) {
            return null;
        }

        $v = (float) $tRaw;

        $medianFallback = ($minF + $maxF) / 2;
        $medianRaw = $stats['median'] ?? null;
        $median = ($medianRaw !== null && is_numeric($medianRaw)) ? (float) $medianRaw : $medianFallback;

        $higherIsBetter = (bool) ($rule['higher_is_better'] ?? true);

        /** @var float $t 0 = red (good), 0.5 = white, 1 = blue (poor) when higher_is_better */
        if ($higherIsBetter) {
            if ($v <= $median) {
                $t = $median - $minF < $eps ? 0.5 : 0.5 + (0.5 * ($median - $v)) / ($median - $minF);
            } else {
                $t = $maxF - $median < $eps ? 0.5 : 0.5 - (0.5 * ($v - $median)) / ($maxF - $median);
            }
        } elseif ($v <= $median) {
            $t = $median - $minF < $eps ? 0.5 : (0.5 * ($v - $minF)) / ($median - $minF);
        } else {
            $t = $maxF - $median < $eps ? 0.5 : 0.5 + (0.5 * ($v - $median)) / ($maxF - $median);
        }

        $t = min(1.0, max(0.0, $t));

        $redR = 255;
        $redG = 0;
        $redB = 0;
        $blueR = 90;
        $blueG = 125;
        $blueB = 188;

        if ($t <= 0.5) {
            $linearU = $t / 0.5;
            $u = $linearU ** 1.12;
            $r = (int) round($redR + (255 - $redR) * $u);
            $g = (int) round($redG + (255 - $redG) * $u);
            $b = (int) round($redB + (255 - $redB) * $u);
        } else {
            $linearU = ($t - 0.5) / 0.5;
            $u = 1 - (1 - $linearU) ** 2;
            $r = (int) round(255 + ($blueR - 255) * $u);
            $g = (int) round(255 + ($blueG - 255) * $u);
            $b = (int) round(255 + ($blueB - 255) * $u);
        }

        $whiteText = $t <= 0.15 || $t >= 0.85;
        $color = $whiteText ? '#ffffff' : '#111827';

        return sprintf('background-color: rgb(%d,%d,%d); color: %s;', $r, $g, $b, $color);
    }
}
