<?php

namespace App\Support;

final class NoteGradeInputAppearance
{
    private const NAVY_R = 12;

    private const NAVY_G = 35;

    private const NAVY_B = 64;

    /** Grade 3 anchor blue (#6A82C1). */
    private const THREE_R = 106;

    private const THREE_G = 130;

    private const THREE_B = 193;

    /** Grade 5 midpoint tile (pale pink #FADADD). */
    private const FIVE_R = 250;

    private const FIVE_G = 218;

    private const FIVE_B = 221;

    /** Grade 6 anchor (#F28080). */
    private const SIX_R = 242;

    private const SIX_G = 128;

    private const SIX_B = 128;

    /** Grade 7 top (#E93423). */
    private const RED_R = 233;

    private const RED_G = 52;

    private const RED_B = 35;

    /**
     * Style for the grade number control: navy (2) → #6A82C1 (3) → #FADADD (5) → #F28080 (6) → #E93423 (7).
     * Digits are white on every filled value except 5 (pale pink tile → dark text).
     */
    public static function inputStyle(?int $value, int $min, int $max): string
    {
        if ($value === null) {
            return 'background-color: #ffffff; border-color: #cbd5e1; color: #0f172a; font-weight: 700;';
        }

        $value = max($min, min($max, $value));

        [$r, $g, $b] = self::rgbForValue($value, $min, $max);

        if ($value === 5) {
            $textColor = '#0f172a';
            $borderColor = '#cbd5e1';
        } else {
            $textColor = '#ffffff';
            $borderColor = 'rgba(255,255,255,0.35)';
        }

        return "background-color: rgb({$r},{$g},{$b}); border-color: {$borderColor}; color: {$textColor}; font-weight: 700;";
    }

    /**
     * Profile grade summary cells: same fill/text contrast as {@see inputStyle} without a control border.
     */
    public static function summaryCellStyle(?int $value, int $min, int $max): string
    {
        if ($value === null) {
            return 'background-color: #ffffff; color: #0f172a; font-weight: 700;';
        }

        $value = max($min, min($max, $value));

        [$r, $g, $b] = self::rgbForValue($value, $min, $max);

        $textColor = $value === 5 ? '#0f172a' : '#ffffff';

        return "background-color: rgb({$r},{$g},{$b}); color: {$textColor}; font-weight: 700;";
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function rgbForValue(int $value, int $min, int $max): array
    {
        $mid = (int) round(($min + $max) / 2);

        if ($max <= $min) {
            return [self::NAVY_R, self::NAVY_G, self::NAVY_B];
        }

        if ($value > $mid) {
            if ($min <= 6 && $max >= 6 && $mid < 6) {
                if ($value <= 6) {
                    $den = max(1, 6 - $mid);
                    $u = ($value - $mid) / $den;

                    return [
                        (int) round(self::FIVE_R + (self::SIX_R - self::FIVE_R) * $u),
                        (int) round(self::FIVE_G + (self::SIX_G - self::FIVE_G) * $u),
                        (int) round(self::FIVE_B + (self::SIX_B - self::FIVE_B) * $u),
                    ];
                }

                $den = max(1, $max - 6);
                $u = ($value - 6) / $den;

                return [
                    (int) round(self::SIX_R + (self::RED_R - self::SIX_R) * $u),
                    (int) round(self::SIX_G + (self::RED_G - self::SIX_G) * $u),
                    (int) round(self::SIX_B + (self::RED_B - self::SIX_B) * $u),
                ];
            }

            $den = max(1, $max - $mid);
            $u = ($value - $mid) / $den;

            return [
                (int) round(self::FIVE_R + (self::RED_R - self::FIVE_R) * $u),
                (int) round(self::FIVE_G + (self::RED_G - self::FIVE_G) * $u),
                (int) round(self::FIVE_B + (self::RED_B - self::FIVE_B) * $u),
            ];
        }

        if ($min <= 3 && $mid >= 3) {
            if ($value <= 3) {
                $den = max(1, 3 - $min);
                $u = ($value - $min) / $den;

                return [
                    (int) round(self::NAVY_R + (self::THREE_R - self::NAVY_R) * $u),
                    (int) round(self::NAVY_G + (self::THREE_G - self::NAVY_G) * $u),
                    (int) round(self::NAVY_B + (self::THREE_B - self::NAVY_B) * $u),
                ];
            }

            $den = max(1, $mid - 3);
            $u = ($value - 3) / $den;

            return [
                (int) round(self::THREE_R + (self::FIVE_R - self::THREE_R) * $u),
                (int) round(self::THREE_G + (self::FIVE_G - self::THREE_G) * $u),
                (int) round(self::THREE_B + (self::FIVE_B - self::THREE_B) * $u),
            ];
        }

        $den = max(1, $mid - $min);
        $u = ($value - $min) / $den;

        return [
            (int) round(self::NAVY_R + (self::FIVE_R - self::NAVY_R) * $u),
            (int) round(self::NAVY_G + (self::FIVE_G - self::NAVY_G) * $u),
            (int) round(self::NAVY_B + (self::FIVE_B - self::NAVY_B) * $u),
        ];
    }
}
