<?php

namespace App\Support;

final class HsRangerTraitsDisplay
{
    /**
     * Format OPS / ISO style stats as three decimal places. Values with |x| < 1 use a leading dot (e.g. .823); 1.000+ keeps a digit before the decimal.
     */
    public static function formatThreeDecimals(?string $raw): string
    {
        if ($raw === null) {
            return PlayerSheetPlaceholder::CELL;
        }
        $t = trim((string) $raw);
        if (PlayerSheetPlaceholder::isEmptyDisplay($t)) {
            return PlayerSheetPlaceholder::CELL;
        }
        $n = str_replace([',', '%', ' '], '', $t);
        if ($n === '' || ! is_numeric($n)) {
            return $t;
        }

        $s = number_format((float) $n, 3, '.', '');
        if (str_starts_with($s, '-0.')) {
            return '-.'.substr($s, 3);
        }
        if (str_starts_with($s, '0.')) {
            return substr($s, 1);
        }

        return $s;
    }

    /**
     * BB% / K% for profile tables: one decimal and a % sign.
     * Treats values in (0,1] without a % as decimal rates (e.g. 0.195 → 19.5%); otherwise uses the numeric value as a percent (e.g. 19.5 → 19.5%).
     */
    public static function formatPercentRateForDisplay(?string $raw): string
    {
        if ($raw === null) {
            return PlayerSheetPlaceholder::CELL;
        }
        $t = trim((string) $raw);
        if (PlayerSheetPlaceholder::isEmptyDisplay($t)) {
            return PlayerSheetPlaceholder::CELL;
        }
        $hadPercent = str_contains($t, '%');
        $n = str_replace([',', '%', ' '], '', $t);
        if ($n === '' || ! is_numeric($n)) {
            return $t;
        }
        $v = (float) $n;
        if (! $hadPercent && $v >= 0.0 && $v <= 1.0 + 1.0e-6) {
            $v *= 100.0;
        }

        return sprintf('%.1f%%', round($v, 4));
    }
}
