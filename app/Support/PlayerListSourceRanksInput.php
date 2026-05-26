<?php

namespace App\Support;

use Illuminate\Validation\Rules\In;

/**
 * Maps optional list-form / inline-edit inputs to {@see Player::$source_ranks} JSON keys.
 */
final class PlayerListSourceRanksInput
{
    /**
     * @return array<string, array<int, string|In>>
     */
    public static function validationRules(): array
    {
        return [
            'source_mdl' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'source_mlb' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'source_espn' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'source_law' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'source_fb' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'source_ba' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated  Request data including source_* keys when present.
     * @param  array<string, int|string|float|null>|null  $existing  Current JSON column value.
     * @return array<string, int>|null Normalized ranks or null when empty.
     */
    public static function mergeIntoSourceRanks(?array $existing, array $validated): ?array
    {
        $map = [
            'source_mdl' => 'model',
            'source_mlb' => 'mlb',
            'source_espn' => 'espn',
            'source_law' => 'law',
            'source_fb' => 'fangraphs',
            'source_ba' => 'ba',
        ];

        $merged = is_array($existing) ? $existing : [];

        foreach ($map as $inputKey => $jsonKey) {
            if (! array_key_exists($inputKey, $validated)) {
                continue;
            }
            $v = $validated[$inputKey];
            if ($v === null || $v === '') {
                unset($merged[$jsonKey]);
            } else {
                $merged[$jsonKey] = (int) $v;
            }
        }

        return $merged === [] ? null : $merged;
    }
}
