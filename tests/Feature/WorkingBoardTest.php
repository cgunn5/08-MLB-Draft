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
        $this->actingAs($user)
            ->get(route('board.index'))
            ->assertOk()
            ->assertSee('MASTER BOARD', false)
            ->assertSee('NCAA BOARD', false)
            ->assertSee('HS BOARD', false)
            ->assertSee('⚰️', false)
            ->assertSee('setActiveBoard', false)
            ->assertSee('>Master<', false)
            ->assertSee('>HS<', false)
            ->assertSee('>NCAA<', false);
    }

    public function test_board_player_pool_includes_bat_grade_fields(): void
    {
        $user = User::factory()->admin()->create();
        Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Bat',
            'last_name' => 'Avg',
            'grade_perf' => 6,
            'grade_approach' => 4,
            'grade_contact' => 5,
            'grade_damage' => 6,
            'grade_swing' => 5,
        ]);

        $this->actingAs($user)
            ->get(route('board.index'))
            ->assertOk()
            ->assertSee('BAT', false)
            ->assertSee('"bat_grade":5.2', false);
    }

    public function test_board_player_pool_includes_all_players_from_players_list(): void
    {
        $user = User::factory()->admin()->create();
        $complete = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Will',
            'last_name' => 'Brick',
        ]);
        $incomplete = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Incomplete',
            'last_name' => 'Player',
        ]);

        $res = $this->actingAs($user)->get(route('board.index'));
        $res->assertOk();
        $res->assertSee('board-picker-input-hs', false);
        $res->assertSee('board-picker-listbox-hs', false);
        $res->assertSee('Type player name', false);
        $res->assertSee('Add selected to round', false);
        $res->assertSee('aria-multiselectable="true"', false);
        $res->assertSee('"player_id":'.$complete->id, false);
        $res->assertSee('"player_id":'.$incomplete->id, false);
        $res->assertSee('boardPlayerPicker', false);
        $res->assertSee('working-boards-config', false);
        $res->assertSee('"label":"BRICK, WILL"', false);
        $res->assertSee('"label":"PLAYER, INCOMPLETE"', false);
    }

    public function test_board_player_pool_lists_complete_profiles_first_with_flag(): void
    {
        $user = User::factory()->admin()->create();
        $complete = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Will',
            'last_name' => 'Brick',
            'master_take' => 'High-end catcher prospect.',
            'note_performance' => 'Strong summer.',
            'note_approach_miss' => 'Advanced approach.',
            'note_pitch_coverage' => 'Handles velo.',
            'note_engine' => 'Plus power.',
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

        $html = $this->actingAs($user)->get(route('board.index'))->assertOk()->getContent();
        $brickPos = strpos($html, '"label":"BRICK, WILL"');
        $incompletePos = strpos($html, '"label":"PLAYER, INCOMPLETE"');

        $this->assertNotFalse($brickPos);
        $this->assertNotFalse($incompletePos);
        $this->assertLessThan($incompletePos, $brickPos);
        $this->assertStringContainsString('"profile_complete":true', $html);
        $this->assertStringContainsString('Add all complete profiles to round', $html);
        $this->assertStringContainsString('Add all players to round', $html);
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

        $payload = $this->boardsPayload([
            WorkingBoardEntry::BOARD_HS => [
                '1' => [
                    ['player_id' => $a->id, 'confidence' => '1', 'risk' => '1'],
                ],
                '2' => [
                    ['player_id' => $b->id, 'confidence' => '', 'risk' => '2'],
                ],
            ],
        ]);

        $this->actingAs($user)->patchJson(route('board.update'), $payload)->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('working_board_entries', [
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_HS,
            'player_id' => $a->id,
            'round_key' => '1',
            'sort_order' => 0,
            'confidence' => '1',
            'risk' => '1',
        ]);
        $this->assertDatabaseHas('working_board_entries', [
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_HS,
            'player_id' => $b->id,
            'round_key' => '2',
            'sort_order' => 0,
            'risk' => '2',
        ]);
        $this->assertSame(2, WorkingBoardEntry::query()->where('user_id', $user->id)->count());
    }

    public function test_board_patch_persists_coffin_round(): void
    {
        $user = User::factory()->admin()->create();
        $player = Player::factory()->create(['player_pool' => 'hs']);

        $payload = $this->boardsPayload([
            WorkingBoardEntry::BOARD_HS => [
                WorkingBoardEntry::ROUND_COFFIN => [
                    ['player_id' => $player->id, 'confidence' => '', 'risk' => ''],
                ],
            ],
        ]);

        $this->actingAs($user)->patchJson(route('board.update'), $payload)->assertOk();

        $this->assertDatabaseHas('working_board_entries', [
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_HS,
            'player_id' => $player->id,
            'round_key' => WorkingBoardEntry::ROUND_COFFIN,
        ]);
    }

    public function test_board_patch_persists_ncaa_board(): void
    {
        $user = User::factory()->admin()->create();
        $player = Player::factory()->create(['player_pool' => 'ncaa', 'last_name' => 'Collegiate', 'first_name' => 'Star']);

        $payload = $this->boardsPayload([
            WorkingBoardEntry::BOARD_NCAA => [
                '1' => [
                    ['player_id' => $player->id, 'confidence' => '5', 'risk' => '1'],
                ],
            ],
        ]);

        $this->actingAs($user)->patchJson(route('board.update'), $payload)->assertOk();

        $this->assertDatabaseHas('working_board_entries', [
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_NCAA,
            'player_id' => $player->id,
            'round_key' => '1',
        ]);
    }

    public function test_board_patch_rejects_duplicate_player_on_same_board(): void
    {
        $user = User::factory()->admin()->create();
        $a = Player::factory()->create(['player_pool' => 'hs']);

        $payload = $this->boardsPayload([
            WorkingBoardEntry::BOARD_HS => [
                '1' => [['player_id' => $a->id, 'confidence' => '', 'risk' => '']],
                '2' => [['player_id' => $a->id, 'confidence' => '', 'risk' => '']],
            ],
        ]);

        $this->actingAs($user)->patchJson(route('board.update'), $payload)->assertStatus(422);
    }

    public function test_board_patch_rejects_non_hs_player_on_hs_board(): void
    {
        $user = User::factory()->admin()->create();
        $p = Player::factory()->create(['player_pool' => 'ncaa']);

        $payload = $this->boardsPayload([
            WorkingBoardEntry::BOARD_HS => [
                '1' => [['player_id' => $p->id, 'confidence' => '', 'risk' => '']],
            ],
        ]);

        $this->actingAs($user)->patchJson(route('board.update'), $payload)->assertStatus(422);
    }

    /**
     * @param  array<string, array<string, list<array<string, mixed>>>>  $roundOverrides
     * @return array{boards: array<string, array{rounds: array<string, list<array<string, mixed>>>>}}
     */
    private function boardsPayload(array $roundOverrides = []): array
    {
        $boards = [];
        foreach (WorkingBoardEntry::BOARD_TYPES as $boardType) {
            $rounds = [];
            foreach (WorkingBoardEntry::ROUND_KEYS as $rk) {
                $rounds[$rk] = [];
            }
            if (isset($roundOverrides[$boardType])) {
                foreach ($roundOverrides[$boardType] as $rk => $list) {
                    $rounds[$rk] = $list;
                }
            }
            $boards[$boardType] = ['rounds' => $rounds];
        }

        return ['boards' => $boards];
    }
}
