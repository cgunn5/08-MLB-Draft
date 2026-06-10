<?php

namespace App\Support;

final class GradeScaleAppearance
{
    /** @var list array{grade: float, hex: string}> */
    private const ANCHOR_STOPS = [
        ['grade' => 3.0, 'hex' => '#5A8AC6'],
        ['grade' => 4.0, 'hex' => '#ACC3E2'],
        ['grade' => 4.5, 'hex' => '#D3E0F0'],
        ['grade' => 5.0, 'hex' => '#FFFFFF'],
        ['grade' => 5.5, 'hex' => '#FBD8DB'],
        ['grade' => 6.0, 'hex' => '#FAB3B5'],
        ['grade' => 7.0, 'hex' => '#F9696A'],
    ];

    public static function summaryCellStyle(?float $value): string
    {
        return self::cellStyle($value, 700);
    }

    public static function profileChipCellStyle(?float $value): string
    {
        return self::cellStyle($value, 400);
    }

    public static function hexForGrade(?float $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $grade = max(3.0, min(7.0, $value));
        $stops = self::ANCHOR_STOPS;

        if ($grade <= $stops[0]['grade']) {
            return $stops[0]['hex'];
        }

        $last = $stops[count($stops) - 1];
        if ($grade >= $last['grade']) {
            return $last['hex'];
        }

        for ($i = 0; $i < count($stops) - 1; $i++) {
            $low = $stops[$i];
            $high = $stops[$i + 1];
            if ($grade < $low['grade'] || $grade > $high['grade']) {
                continue;
            }

            if (self::floatEquals($grade, $low['grade'])) {
                return $low['hex'];
            }

            if (self::floatEquals($grade, $high['grade'])) {
                return $high['hex'];
            }

            $t = ($grade - $low['grade']) / ($high['grade'] - $low['grade']);

            return self::lerpHex($low['hex'], $high['hex'], $t);
        }

        return $last['hex'];
    }

    private static function cellStyle(?float $value, int $fontWeight): string
    {
        $hex = self::hexForGrade($value);
        if ($hex === null) {
            return "background-color:#ffffff;color:#000000;font-weight:{$fontWeight};";
        }

        $textColor = self::textColorForHex($hex);

        return "background-color:{$hex};color:{$textColor};font-weight:{$fontWeight};";
    }

    private static function textColorForHex(string $hex): string
    {
        $rgb = self::parseHexToRgb($hex);
        if ($rgb === null) {
            return '#000000';
        }

        $luminance = self::relativeLuminance($rgb);

        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }

    private static function lerpHex(string $from, string $to, float $t): string
    {
        $a = self::parseHexToRgb($from);
        $b = self::parseHexToRgb($to);
        if ($a === null || $b === null) {
            return $from;
        }

        $u = max(0.0, min(1.0, $t));

        return sprintf(
            '#%02X%02X%02X',
            (int) round($a[0] + ($b[0] - $a[0]) * $u),
            (int) round($a[1] + ($b[1] - $a[1]) * $u),
            (int) round($a[2] + ($b[2] - $a[2]) * $u),
        );
    }

    /**
     * @return array{0: int, 1: int, 2: int}|null
     */
    private static function parseHexToRgb(string $hex): ?array
    {
        if (preg_match('/^#([0-9a-f]{6})$/i', $hex, $matches) !== 1) {
            return null;
        }

        $value = $matches[1];

        return [
            hexdec(substr($value, 0, 2)),
            hexdec(substr($value, 2, 2)),
            hexdec(substr($value, 4, 2)),
        ];
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

    private static function floatEquals(float $a, float $b): bool
    {
        return abs($a - $b) < 1e-6;
    }
}
