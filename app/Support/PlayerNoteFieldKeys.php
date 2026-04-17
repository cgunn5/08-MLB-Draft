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
                ['key' => 'master_take', 'label' => 'Master Take'],
                ['key' => 'note_performance', 'label' => 'Performance'],
                ['key' => 'note_approach_miss', 'label' => 'Approach & Miss'],
                ['key' => 'note_pitch_coverage', 'label' => 'Pitch Coverage'],
                ['key' => 'note_engine', 'label' => 'Engine'],
                ['key' => 'note_left_right', 'label' => 'Left / Right'],
                ['key' => 'note_swing', 'label' => 'Swing'],
            ],
            'hs' => [
                ['key' => 'master_take', 'label' => 'Master Take'],
                ['key' => 'note_performance', 'label' => 'Circuit Stats'],
                ['key' => 'note_approach_miss', 'label' => 'Approach & Miss'],
                ['key' => 'note_pitch_coverage', 'label' => 'Adjustability'],
                ['key' => 'note_engine', 'label' => 'Impact / Damage'],
                ['key' => 'note_swing', 'label' => 'Swing'],
            ],
            default => [],
        };
    }
}
