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
    /**
     * Map a CSV / sheet cell to a canonical bucket ({@see BUCKET_VALUES}) so comp filtering matches
     * UI values even when exports use en dash, em dash, or extra spaces (common from Excel).
     */
    public static function normalizeBucketCell(string $raw): ?string
    {
        $t = trim($raw);
        if ($t === '') {
            return null;
        }

        $t = (string) preg_replace('/[\x{2013}\x{2014}\x{2212}\x{FE58}\x{FE63}\x{FF0D}]/u', '-', $t);
        $t = trim((string) preg_replace('/\s+/u', ' ', $t));

        foreach (self::BUCKET_VALUES as $b) {
            if (strcasecmp($t, $b) === 0) {
                return $b;
            }
        }

        $compact = strtolower((string) preg_replace('/\s+/u', '', $t));
        foreach (self::BUCKET_VALUES as $b) {
            $bc = strtolower((string) preg_replace('/\s+/u', '', $b));
            if ($compact === $bc) {
                return $b;
            }
        }

        return null;
    }

    public static function cellMatchesBucket(string $raw, string $normalizedCompScope): bool
    {
        $cellBucket = self::normalizeBucketCell($raw);

        return $cellBucket !== null && strcasecmp($cellBucket, $normalizedCompScope) === 0;
    }

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
