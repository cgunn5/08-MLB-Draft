<?php

namespace App\Support;

final class PlayerNoteFieldKeys
{
    /**
     * @return list<string>
     */
    public static function forPool(string $pool): array
    {
        return match ($pool) {
            'ncaa' => [
                'master_take',
                'note_performance',
                'note_approach_miss',
                'note_pitch_coverage',
                'note_engine',
                'note_left_right',
                'note_swing',
            ],
            'hs' => [
                'master_take',
                'note_performance',
                'note_approach_miss',
                'note_pitch_coverage',
                'note_engine',
                'note_swing',
            ],
            default => [],
        };
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function sectionsForPool(string $pool): array
    {
        return match ($pool) {
            'ncaa' => [
                ['key' => 'master_take', 'label' => 'Player Summary'],
                ['key' => 'note_performance', 'label' => 'Performance'],
                ['key' => 'note_approach_miss', 'label' => 'Approach / Miss'],
                ['key' => 'note_pitch_coverage', 'label' => 'Pitch Coverage'],
                ['key' => 'note_engine', 'label' => 'Engine'],
                ['key' => 'note_left_right', 'label' => 'Left / Right'],
                ['key' => 'note_swing', 'label' => 'Swing'],
            ],
            'hs' => [
                ['key' => 'master_take', 'label' => 'Player Summary'],
                ['key' => 'note_performance', 'label' => 'Circuit Stats'],
                ['key' => 'note_approach_miss', 'label' => 'Approach / Miss'],
                ['key' => 'note_pitch_coverage', 'label' => 'Adjustability'],
                ['key' => 'note_engine', 'label' => 'Impact / Damage'],
                ['key' => 'note_swing', 'label' => 'Swing'],
            ],
            default => [],
        };
    }

    /**
     * Profile summary grade column updated alongside this note section (same row as on profile grid).
     */
    public static function gradeAttributeForNoteField(string $field, string $pool): ?string
    {
        if ($field === 'master_take') {
            return 'grade_role';
        }

        return match ($field) {
            'note_performance' => 'grade_perf',
            'note_approach_miss' => 'grade_approach',
            'note_pitch_coverage' => $pool === 'ncaa' ? 'grade_adj' : 'grade_contact',
            'note_engine' => 'grade_damage',
            'note_left_right' => 'grade_contact',
            'note_swing' => 'grade_swing',
            default => null,
        };
    }

    /**
     * @return array{min: int, max: int}
     */
    public static function gradeBoundsForNoteField(string $field): array
    {
        return ['min' => 2, 'max' => 7];
    }

    /**
     * Laravel validation rules for a submitted grade (half-point steps: 2, 2.5, …, 7).
     *
     * @return list<string>
     */
    public static function gradeValueValidationRules(string $field): array
    {
        $b = self::gradeBoundsForNoteField($field);

        return [
            'nullable',
            'numeric',
            'min:'.$b['min'],
            'max:'.$b['max'],
            'multiple_of:0.5',
        ];
    }

    /**
     * @return array{0: ?float, 1: string} Style interpolation value (or null), HTML input value
     */
    public static function gradeFormFieldState(mixed $oldGrade, ?string $storedRaw): array
    {
        $pick = null;
        if ($oldGrade !== null && $oldGrade !== '' && is_numeric((string) $oldGrade)) {
            $pick = (string) $oldGrade;
        } elseif ($storedRaw !== null && $storedRaw !== '' && is_numeric($storedRaw)) {
            $pick = $storedRaw;
        }
        if ($pick === null) {
            return [null, ''];
        }
        $f = (float) $pick;
        $input = abs($f - round($f)) < 1e-9
            ? (string) (int) round($f)
            : number_format($f, 1, '.', '');

        return [$f, $input];
    }
}
