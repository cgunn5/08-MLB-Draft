<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use App\Models\WorkingBoardEntry;
use App\Support\PlayerProfileCompleteness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkingBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_board(): void
    {
        $this->get(route('board.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_board(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('board.index'))->assertOk()->assertSee('HS BOARD', false);
    }

    public function test_board_player_pool_only_includes_complete_profiles(): void
    {
        $user = User::factory()->create();
        $complete = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Will',
            'last_name' => 'Brick',
            'school' => 'Test HS (TX)',
            'position' => 'C',
            'master_take' => 'High-end catcher prospect.',
            'note_performance' => 'Strong summer.',
            'note_approach_miss' => 'Advanced approach.',
            'note_pitch_coverage' => 'Adjusts to pitches.',
            'note_engine' => 'Plus damage.',
            'note_swing' => 'Compact.',
            'grade_role' => 6,
            'grade_perf' => 5.5,
            'grade_approach' => 6,
            'grade_contact' => 5.5,
            'grade_damage' => 6,
            'grade_swing' => 5.5,
        ]);
        $incomplete = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Incomplete',
            'last_name' => 'Player',
        ]);

        $this->assertTrue(PlayerProfileCompleteness::isComplete($complete));
        $this->assertFalse(PlayerProfileCompleteness::isComplete($incomplete));

        $res = $this->actingAs($user)->get(route('board.index'));
        $res->assertOk();
        $res->assertSee('"player_id":'.$complete->id, false);
        $res->assertDontSee('"player_id":'.$incomplete->id, false);
    }

    public function test_board_patch_persists_rounds(): void
    {
        $user = User::factory()->admin()->create();
        $a = Player::factory()->create([
            'player_pool' => 'hs',
            'last_name' => 'Alpha',
            'first_name' => 'A',
            'school' => 'Test HS (TX)',
            'position' => 'SS',
            'grade_role' => 5.5,
            'grade_swing' => 6,
        ]);
        $b = Player::factory()->create([
            'player_pool' => 'hs',
            'last_name' => 'Beta',
            'first_name' => 'B',
        ]);

        $payload = [
            'rounds' => [
                '1' => [
                    ['player_id' => $a->id, 'confidence' => 'HIGH', 'risk' => 'LOW'],
                ],
                '2' => [
                    ['player_id' => $b->id, 'confidence' => '', 'risk' => 'MEDIUM'],
                ],
                '3' => [],
                '4+' => [],
                '10+' => [],
            ],
        ];

        $this->actingAs($user)->patchJson(route('board.update'), $payload)->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('working_board_entries', [
            'user_id' => $user->id,
            'player_id' => $a->id,
            'round_key' => '1',
            'sort_order' => 0,
            'confidence' => 'HIGH',
            'risk' => 'LOW',
        ]);
        $this->assertDatabaseHas('working_board_entries', [
            'user_id' => $user->id,
            'player_id' => $b->id,
            'round_key' => '2',
            'sort_order' => 0,
            'risk' => 'MEDIUM',
        ]);
        $this->assertSame(2, WorkingBoardEntry::query()->where('user_id', $user->id)->count());
    }

    public function test_board_patch_rejects_duplicate_player(): void
    {
        $user = User::factory()->admin()->create();
        $a = Player::factory()->create(['player_pool' => 'hs']);

        $payload = [
            'rounds' => [
                '1' => [['player_id' => $a->id, 'confidence' => '', 'risk' => '']],
                '2' => [['player_id' => $a->id, 'confidence' => '', 'risk' => '']],
                '3' => [],
                '4+' => [],
                '10+' => [],
            ],
        ];

        $this->actingAs($user)->patchJson(route('board.update'), $payload)->assertStatus(422);
    }

    public function test_board_patch_rejects_non_hs_player(): void
    {
        $user = User::factory()->admin()->create();
        $p = Player::factory()->create(['player_pool' => 'ncaa']);

        $payload = [
            'rounds' => [
                '1' => [['player_id' => $p->id, 'confidence' => '', 'risk' => '']],
                '2' => [],
                '3' => [],
                '4+' => [],
                '10+' => [],
            ],
        ];

        $this->actingAs($user)->patchJson(route('board.update'), $payload)->assertStatus(422);
    }
}
