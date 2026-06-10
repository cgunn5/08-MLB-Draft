<?php

namespace App\Support;

use App\Models\Player;

final class BatGradeAppearance
{
    private const HEX_LOW = '#5A8AC6';

    private const HEX_MID = '#FFFFFF';

    private const HEX_HIGH = '#F9696A';

    /**
     * @return array{min: ?float, max: ?float, median: ?float}
     */
    public static function appWideBounds(): array
    {
        $values = Player::query()
            ->get()
            ->map(fn (Player $player): ?float => $player->batGrade())
            ->filter(fn (?float $value): bool => $value !== null)
            ->values()
            ->all();

        return WorkingBoardCellAppearance::percentileBoundsFromValues($values);
    }

    public static function profileChipCellStyle(?float $value): string
    {
        return self::cellStyle($value, self::appWideBounds(), 400);
    }

    /**
     * @param  array{min: ?float, max: ?float, median: ?float}  $bounds
     */
    public static function cellStyle(?float $value, array $bounds, int $fontWeight = 400): string
    {
        if ($value === null) {
            return self::styleForBackground('#ffffff', $fontWeight);
        }

        $min = $bounds['min'];
        $max = $bounds['max'];
        $median = $bounds['median'];

        if ($min === null || $max === null || $median === null) {
            return self::styleForBackground('#ffffff', $fontWeight);
        }

        if ($max === $min) {
            return self::styleForBackground('#ffffff', $fontWeight);
        }

        if ($value >= $median) {
            $den = max(1e-9, $max - $median);
            $t = ($value - $median) / $den;
            $hex = self::lerpHex(self::HEX_MID, self::HEX_HIGH, $t);
        } else {
            $den = max(1e-9, $median - $min);
            $t = ($value - $min) / $den;
            $hex = self::lerpHex(self::HEX_LOW, self::HEX_MID, $t);
        }

        return self::styleForBackground($hex, $fontWeight);
    }

    private static function lerpHex(string $a, string $b, float $t): string
    {
        $u = max(0.0, min(1.0, $t));
        $rgbA = self::parseBackgroundToRgb($a);
        $rgbB = self::parseBackgroundToRgb($b);
        if ($rgbA === null || $rgbB === null) {
            return $a;
        }

        $rgb = [
            (int) round($rgbA[0] + ($rgbB[0] - $rgbA[0]) * $u),
            (int) round($rgbA[1] + ($rgbB[1] - $rgbA[1]) * $u),
            (int) round($rgbA[2] + ($rgbB[2] - $rgbA[2]) * $u),
        ];

        return sprintf('#%02X%02X%02X', $rgb[0], $rgb[1], $rgb[2]);
    }

    private static function styleForBackground(string $background, int $fontWeight): string
    {
        $textColor = self::textColorForBackground($background);

        return "background-color:{$background};color:{$textColor};font-weight:{$fontWeight};";
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
