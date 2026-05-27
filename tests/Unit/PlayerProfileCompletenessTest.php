<?php

namespace Tests\Unit;

use App\Models\Player;
use App\Support\PlayerProfileCompleteness;
use Tests\TestCase;

class PlayerProfileCompletenessTest extends TestCase
{
    public function test_hs_player_is_complete_when_all_notes_and_grades_are_filled(): void
    {
        $player = Player::factory()->make([
            'player_pool' => 'hs',
            'master_take' => 'Corner bat with power.',
            'note_performance' => 'Strong circuit stats.',
            'note_approach_miss' => 'Patient approach.',
            'note_pitch_coverage' => 'Handles velocity.',
            'note_engine' => 'Plus raw power.',
            'note_swing' => 'Compact swing.',
            'grade_role' => 5,
            'grade_perf' => 5.5,
            'grade_approach' => 6,
            'grade_contact' => 5,
            'grade_damage' => 6.5,
            'grade_swing' => 5.5,
        ]);

        $this->assertTrue(PlayerProfileCompleteness::isComplete($player));
    }

    public function test_hs_player_is_incomplete_when_any_note_or_grade_is_missing(): void
    {
        $base = [
            'player_pool' => 'hs',
            'master_take' => 'Corner bat with power.',
            'note_performance' => 'Strong circuit stats.',
            'note_approach_miss' => 'Patient approach.',
            'note_pitch_coverage' => 'Handles velocity.',
            'note_engine' => 'Plus raw power.',
            'note_swing' => 'Compact swing.',
            'grade_role' => 5,
            'grade_perf' => 5.5,
            'grade_approach' => 6,
            'grade_contact' => 5,
            'grade_damage' => 6.5,
            'grade_swing' => 5.5,
        ];

        $missingNote = Player::factory()->make(array_merge($base, ['note_swing' => null]));
        $missingGrade = Player::factory()->make(array_merge($base, ['grade_swing' => null]));

        $this->assertFalse(PlayerProfileCompleteness::isComplete($missingNote));
        $this->assertFalse(PlayerProfileCompleteness::isComplete($missingGrade));
    }

    public function test_ncaa_player_requires_platoon_note_and_grade(): void
    {
        $complete = Player::factory()->make([
            'player_pool' => 'ncaa',
            'master_take' => 'Everyday bat.',
            'note_performance' => 'Strong track record.',
            'note_approach_miss' => 'Elite zone control.',
            'note_pitch_coverage' => 'Adjusts well.',
            'note_engine' => 'Plus bat speed.',
            'note_left_right' => 'No platoon concern.',
            'note_swing' => 'Efficient path.',
            'grade_role' => 6,
            'grade_perf' => 6,
            'grade_approach' => 5.5,
            'grade_adj' => 5,
            'grade_damage' => 6,
            'grade_contact' => 5.5,
            'grade_swing' => 5,
        ]);

        $missingPlatoon = Player::factory()->make(array_merge($complete->getAttributes(), [
            'note_left_right' => null,
        ]));

        $this->assertTrue(PlayerProfileCompleteness::isComplete($complete));
        $this->assertFalse(PlayerProfileCompleteness::isComplete($missingPlatoon));
    }
}
