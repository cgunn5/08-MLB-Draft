<?php

namespace App\Support;

final class WorkingBoardCellAppearance
{
    /** @var array<int, string> */
    private const SCALE_COLORS = [
        1 => '#ec7c77',
        2 => '#f7cac9',
        3 => '#FEE69C',
        4 => '#b8d68c',
        5 => '#7dbd7d',
    ];

    /** @var array<int, string> */
    private const RISK_TEXT_COLORS = [
        1 => '#ec7c77',
        2 => '#f6b283',
        3 => '#F2B705',
        4 => '#b8d68c',
        5 => '#7dbd7d',
    ];

    /** @var array{0: int, 1: int, 2: int} */
    private const BAT_COLOR_RED = [229, 115, 115];

    /** @var array{0: int, 1: int, 2: int} */
    private const BAT_COLOR_WHITE = [255, 255, 255];

    /** @var array{0: int, 1: int, 2: int} */
    private const BAT_COLOR_BLUE = [96, 130, 182];

    public static function confidenceFillStyle(?string $value): string
    {
        if ($value === null || $value === '') {
            return self::cellStyle('#ffffff');
        }

        $n = (int) round((float) $value);
        if ($n < 1 || $n > 5) {
            return self::cellStyle('#ffffff');
        }

        return self::cellStyle(self::SCALE_COLORS[$n]);
    }

    public static function riskTextStyle(?string $value): string
    {
        if ($value === null || $value === '') {
            return 'color:#94a3b8;font-weight:700;';
        }

        $n = (int) round((float) $value);
        if ($n < 1 || $n > 5) {
            return 'color:#94a3b8;font-weight:700;';
        }

        $color = self::RISK_TEXT_COLORS[$n];

        return "color:{$color};font-weight:700;";
    }

    public static function riskFillStyle(?string $value): string
    {
        return self::confidenceFillStyle($value);
    }

    /**
     * @param  array{min: ?float, max: ?float, median: ?float}  $bounds
     */
    public static function percentileCellStyle(?float $value, array $bounds): string
    {
        if ($value === null) {
            return self::cellStyle('#ffffff');
        }

        $min = $bounds['min'];
        $max = $bounds['max'];
        $median = $bounds['median'];

        if ($min === null || $max === null || $median === null) {
            return self::cellStyle('#ffffff');
        }

        if ($max === $min) {
            return self::cellStyle('#ffffff');
        }

        if ($value >= $median) {
            $den = max(1e-9, $max - $median);
            $t = ($value - $median) / $den;
            $rgb = self::lerpRgb(self::BAT_COLOR_WHITE, self::BAT_COLOR_RED, $t);
        } else {
            $den = max(1e-9, $median - $min);
            $t = ($value - $min) / $den;
            $rgb = self::lerpRgb(self::BAT_COLOR_BLUE, self::BAT_COLOR_WHITE, $t);
        }

        return self::cellStyle("rgb({$rgb[0]},{$rgb[1]},{$rgb[2]})");
    }

    /**
     * @param  list<float>  $values
     * @return array{min: ?float, max: ?float, median: ?float}
     */
    public static function percentileBoundsFromValues(array $values): array
    {
        if ($values === []) {
            return ['min' => null, 'max' => null, 'median' => null];
        }

        sort($values);
        $min = $values[0];
        $max = $values[count($values) - 1];
        $mid = intdiv(count($values), 2);
        $median = count($values) % 2 === 1
            ? $values[$mid]
            : ($values[$mid - 1] + $values[$mid]) / 2;

        return ['min' => $min, 'max' => $max, 'median' => $median];
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $a
     * @param  array{0: int, 1: int, 2: int}  $b
     * @return array{0: int, 1: int, 2: int}
     */
    private static function lerpRgb(array $a, array $b, float $t): array
    {
        $u = max(0.0, min(1.0, $t));

        return [
            (int) round($a[0] + ($b[0] - $a[0]) * $u),
            (int) round($a[1] + ($b[1] - $a[1]) * $u),
            (int) round($a[2] + ($b[2] - $a[2]) * $u),
        ];
    }

    private static function cellStyle(string $background): string
    {
        $textColor = self::textColorForBackground($background);

        return "background-color:{$background};color:{$textColor};font-weight:400;";
    }

    private static function textColorForBackground(string $background): string
    {
        $rgb = self::parseBackgroundToRgb($background);
        if ($rgb === null) {
            return '#000000';
        }

        return self::relativeLuminance($rgb) > 0.5 ? '#000000' : '#ffffff';
    }

    /**
     * @return array{0: int, 1: int, 2: int}|null
     */
    private static function parseBackgroundToRgb(string $background): ?array
    {
        $background = trim($background);

        if (preg_match('/^#([0-9a-f]{6})$/i', $background, $matches) === 1) {
            $hex = $matches[1];

            return [
                hexdec(substr($hex, 0, 2)),
                hexdec(substr($hex, 2, 2)),
                hexdec(substr($hex, 4, 2)),
            ];
        }

        if (preg_match('/^rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/i', $background, $matches) === 1) {
            return [(int) $matches[1], (int) $matches[2], (int) $matches[3]];
        }

        return null;
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private static function relativeLuminance(array $rgb): float
    {
        [$r, $g, $b] = array_map(
            fn (int $channel): float => self::srgbChannelToLinear($channel / 255),
            $rgb,
        );

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private static function srgbChannelToLinear(float $channel): float
    {
        return $channel <= 0.03928
            ? $channel / 12.92
            : (($channel + 0.055) / 1.055) ** 2.4;
    }
}
