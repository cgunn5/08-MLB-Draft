<?php

namespace Tests\Unit;

use App\Support\GradeScaleAppearance;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GradeScaleAppearanceTest extends TestCase
{
    #[Test]
    public function anchor_grades_map_to_palette_hex_values(): void
    {
        $this->assertSame('#F9696A', GradeScaleAppearance::hexForGrade(7.0));
        $this->assertSame('#FAB3B5', GradeScaleAppearance::hexForGrade(6.0));
        $this->assertSame('#FBD8DB', GradeScaleAppearance::hexForGrade(5.5));
        $this->assertSame('#FFFFFF', GradeScaleAppearance::hexForGrade(5.0));
        $this->assertSame('#D3E0F0', GradeScaleAppearance::hexForGrade(4.5));
        $this->assertSame('#ACC3E2', GradeScaleAppearance::hexForGrade(4.0));
        $this->assertSame('#5A8AC6', GradeScaleAppearance::hexForGrade(3.0));
    }

    #[Test]
    public function grades_outside_anchor_range_are_clamped(): void
    {
        $this->assertSame('#5A8AC6', GradeScaleAppearance::hexForGrade(2.0));
        $this->assertSame('#F9696A', GradeScaleAppearance::hexForGrade(8.0));
    }

    #[Test]
    public function summary_cell_style_uses_black_text_on_white_anchor(): void
    {
        $this->assertSame(
            'background-color:#FFFFFF;color:#000000;font-weight:700;',
            GradeScaleAppearance::summaryCellStyle(5.0),
        );
    }

    #[Test]
    public function profile_chip_style_uses_black_text_on_light_pink_anchor(): void
    {
        $this->assertSame(
            'background-color:#FAB3B5;color:#000000;font-weight:400;',
            GradeScaleAppearance::profileChipCellStyle(6.0),
        );
    }
}
