<?php

namespace Tests\Unit;

use App\Models\Player;
use PHPUnit\Framework\TestCase;

class PlayerBatGradeTest extends TestCase
{
    public function test_hs_bat_grade_averages_five_traits(): void
    {
        $player = new Player([
            'player_pool' => 'hs',
            'grade_perf' => 6,
            'grade_approach' => 5,
            'grade_contact' => 4,
            'grade_damage' => 6,
            'grade_swing' => 5,
        ]);

        $this->assertSame(5.2, $player->batGrade());
    }

    public function test_ncaa_bat_grade_averages_six_traits(): void
    {
        $player = new Player([
            'player_pool' => 'ncaa',
            'grade_perf' => 6,
            'grade_approach' => 6,
            'grade_damage' => 5,
            'grade_adj' => 5,
            'grade_contact' => 4,
            'grade_swing' => 6,
        ]);

        $this->assertSame(5.333333333333333, $player->batGrade());
    }

    public function test_bat_grade_is_null_when_any_trait_missing(): void
    {
        $player = new Player([
            'player_pool' => 'hs',
            'grade_perf' => 6,
            'grade_approach' => null,
            'grade_contact' => 4,
            'grade_damage' => 6,
            'grade_swing' => 5,
        ]);

        $this->assertNull($player->batGrade());
    }
}
