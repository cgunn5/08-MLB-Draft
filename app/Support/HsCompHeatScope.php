<?php

namespace App\Support;

/**
 * HS profile: optional heat scale vs a draft comp bucket column (e.g. Rnds on HS Stats — Overall).
 */
final class HsCompHeatScope
{
    public const string QUERY_KEY = 'comp_heat';

    /** @var list<string> */
    public const array BUCKET_VALUES = ['1-2', '3-6', '7+'];

    /**
     * @return list<array{value: string|null, label: string}>
     */
    public static function uiOptions(): array
    {
        $out = [
            ['value' => null, 'label' => __('All')],
        ];
        foreach (self::BUCKET_VALUES as $b) {
            $out[] = ['value' => $b, 'label' => $b];
        }

        return $out;
    }

    /**
     * Normalize query input: null = full-dataset (overall) heat; non-null = restrict stats to that bucket.
     */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $t = trim($raw);
        if ($t === '') {
            return null;
        }
        $lower = strtolower($t);
        if ($lower === 'overall' || $lower === 'all') {
            return null;
        }
        foreach (self::BUCKET_VALUES as $b) {
            if (strcasecmp($t, $b) === 0) {
                return $b;
            }
        }

        return null;
    }
}
