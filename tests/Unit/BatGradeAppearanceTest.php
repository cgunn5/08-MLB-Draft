<?php

namespace Tests\Unit;

use App\Models\Player;
use App\Support\BatGradeAppearance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BatGradeAppearanceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function app_wide_bounds_use_all_recorded_bat_grades(): void
    {
        Player::factory()->create([
            'player_pool' => 'hs',
            'grade_perf' => 4,
            'grade_approach' => 4,
            'grade_contact' => 4,
            'grade_damage' => 4,
            'grade_swing' => 4,
        ]);
        Player::factory()->create([
            'player_pool' => 'hs',
            'grade_perf' => 6,
            'grade_approach' => 6,
            'grade_contact' => 6,
            'grade_damage' => 6,
            'grade_swing' => 6,
        ]);
        Player::factory()->create([
            'player_pool' => 'hs',
            'grade_perf' => 5,
            'grade_approach' => 5,
            'grade_contact' => 5,
            'grade_damage' => 5,
            'grade_swing' => 5,
        ]);

        $bounds = BatGradeAppearance::appWideBounds();

        $this->assertSame(4.0, $bounds['min']);
        $this->assertSame(6.0, $bounds['max']);
        $this->assertSame(5.0, $bounds['median']);
    }

    #[Test]
    public function min_max_and_median_values_use_anchor_colors(): void
    {
        $bounds = ['min' => 4.0, 'max' => 6.0, 'median' => 5.0];

        $this->assertSame(
            'background-color:#5A8AC6;color:#ffffff;font-weight:400;',
            BatGradeAppearance::cellStyle(4.0, $bounds),
        );
        $this->assertSame(
            'background-color:#FFFFFF;color:#000000;font-weight:400;',
            BatGradeAppearance::cellStyle(5.0, $bounds),
        );
        $this->assertSame(
            'background-color:#F9696A;color:#ffffff;font-weight:400;',
            BatGradeAppearance::cellStyle(6.0, $bounds),
        );
    }

    #[Test]
    public function null_bat_grade_uses_white_background(): void
    {
        $bounds = ['min' => 4.0, 'max' => 6.0, 'median' => 5.0];

        $this->assertSame(
            'background-color:#ffffff;color:#000000;font-weight:400;',
            BatGradeAppearance::cellStyle(null, $bounds),
        );
    }
}
