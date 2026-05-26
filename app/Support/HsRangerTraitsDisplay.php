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

    /**
     * K/BB style ratios: always two decimal places (e.g. 0.55, 2.50, 10.00).
     */
    public static function formatTwoDecimalRatio(?string $raw): string
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

        return sprintf('%.2f', round((float) $n, 2));
    }

    /**
     * Exit velo / mph-style numbers: one decimal (e.g. 88.5, 105.0).
     */
    public static function formatOneDecimalDisplay(?string $raw): string
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

        return sprintf('%.1f', round((float) $n, 1));
    }

    /**
     * Whole-number display (no fractional part), e.g. swing-decision counts.
     */
    public static function formatIntegerForDisplay(?string $raw): string
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

        return (string) (int) round((float) $n, 0, PHP_ROUND_HALF_UP);
    }
}
