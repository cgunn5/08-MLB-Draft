<?php

namespace Tests\Unit;

use App\Support\HsCompHeatScope;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HsCompHeatScopeTest extends TestCase
{
    #[Test]
    #[DataProvider('normalizeBucketCellProvider')]
    public function normalize_bucket_cell_maps_to_canonical_buckets(?string $expected, string $raw): void
    {
        $this->assertSame($expected, HsCompHeatScope::normalizeBucketCell($raw));
    }

    /**
     * @return array<string, array{0: ?string, 1: string}>
     */
    public static function normalizeBucketCellProvider(): array
    {
        return [
            'empty' => [null, ''],
            'whitespace' => [null, '   '],
            'ascii 1-2' => ['1-2', '1-2'],
            'en dash' => ['1-2', "1\u{2013}2"],
            'em dash' => ['1-2', "1\u{2014}2"],
            'minus sign' => ['3-6', "3\u{2212}6"],
            'spaced plus' => ['7+', '7 +'],
            'trimmed' => ['1-2', '  1-2  '],
        ];
    }

    #[Test]
    public function cell_matches_bucket_compares_canonical_values(): void
    {
        $this->assertTrue(HsCompHeatScope::cellMatchesBucket("1\u{2013}2", '1-2'));
        $this->assertFalse(HsCompHeatScope::cellMatchesBucket('3-6', '1-2'));
        $this->assertFalse(HsCompHeatScope::cellMatchesBucket('', '1-2'));
    }
}
