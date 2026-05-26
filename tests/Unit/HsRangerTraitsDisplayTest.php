<?php

namespace Tests\Unit;

use App\Support\HsRangerTraitsDisplay;
use PHPUnit\Framework\TestCase;

class HsRangerTraitsDisplayTest extends TestCase
{
    public function test_format_two_decimal_ratio(): void
    {
        $this->assertSame('0.55', HsRangerTraitsDisplay::formatTwoDecimalRatio('0.55'));
        $this->assertSame('2.50', HsRangerTraitsDisplay::formatTwoDecimalRatio('2.5'));
        $this->assertSame('10.00', HsRangerTraitsDisplay::formatTwoDecimalRatio('10'));
        $this->assertSame('0.56', HsRangerTraitsDisplay::formatTwoDecimalRatio('0.555'));
    }

    public function test_format_integer_for_display(): void
    {
        $this->assertSame('46', HsRangerTraitsDisplay::formatIntegerForDisplay('45.7'));
        $this->assertSame('45', HsRangerTraitsDisplay::formatIntegerForDisplay('45'));
    }

    public function test_format_one_decimal_display(): void
    {
        $this->assertSame('88.5', HsRangerTraitsDisplay::formatOneDecimalDisplay('88.52'));
        $this->assertSame('105.2', HsRangerTraitsDisplay::formatOneDecimalDisplay('105.18'));
        $this->assertSame('0.5', HsRangerTraitsDisplay::formatOneDecimalDisplay('0.46'));
    }
}
