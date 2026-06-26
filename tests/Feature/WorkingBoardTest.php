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
            ->assertDontSee('⚰️', false)
            ->assertDontSee('working-board-toggle-row', false);
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
        $res->assertSee('board-picker-input-master', false);
        $res->assertSee('board-picker-listbox-master', false);
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
            WorkingBoardEntry::BOARD_MASTER => [
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
            'board_type' => WorkingBoardEntry::BOARD_MASTER,
            'player_id' => $a->id,
            'round_key' => '1',
            'sort_order' => 0,
            'confidence' => '1',
            'risk' => '1',
        ]);
        $this->assertDatabaseHas('working_board_entries', [
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_MASTER,
            'player_id' => $b->id,
            'round_key' => '2',
            'sort_order' => 0,
            'risk' => '2',
        ]);
        $this->assertSame(2, WorkingBoardEntry::query()->where('user_id', $user->id)->count());
    }

    public function test_board_patch_persists_non_target_divider_in_round(): void
    {
        $user = User::factory()->admin()->create();
        $player = Player::factory()->create(['player_pool' => 'hs']);

        $payload = $this->boardsPayload([
            WorkingBoardEntry::BOARD_MASTER => [
                'post-10' => [
                    ['entry_type' => WorkingBoardEntry::ENTRY_TYPE_NON_TARGET_DIVIDER],
                    ['player_id' => $player->id, 'confidence' => '', 'risk' => ''],
                ],
            ],
        ]);

        $this->actingAs($user)->patchJson(route('board.update'), $payload)->assertOk();

        $this->assertDatabaseHas('working_board_entries', [
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_MASTER,
            'entry_type' => WorkingBoardEntry::ENTRY_TYPE_NON_TARGET_DIVIDER,
            'player_id' => null,
            'round_key' => 'post-10',
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('working_board_entries', [
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_MASTER,
            'player_id' => $player->id,
            'round_key' => 'post-10',
            'sort_order' => 1,
        ]);
    }

    public function test_board_migrates_legacy_coffin_round_into_post_10_with_non_target_divider(): void
    {
        $user = User::factory()->admin()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'last_name' => 'Coffin',
            'first_name' => 'Case',
        ]);

        WorkingBoardEntry::query()->create([
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_MASTER,
            'player_id' => $player->id,
            'round_key' => WorkingBoardEntry::ROUND_COFFIN,
            'sort_order' => 0,
            'confidence' => '2',
            'risk' => '3',
        ]);

        $html = $this->actingAs($user)->get(route('board.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('⚰️', $html);
        $this->assertStringContainsString('Non-Targets', $html);
        $this->assertStringContainsString('"label":"COFFIN, CASE"', $html);
    }

    public function test_board_patch_persists_ncaa_board(): void
    {
        $user = User::factory()->admin()->create();
        $player = Player::factory()->create(['player_pool' => 'ncaa', 'last_name' => 'Collegiate', 'first_name' => 'Star']);

        $payload = $this->boardsPayload([
            WorkingBoardEntry::BOARD_MASTER => [
                '1' => [
                    ['player_id' => $player->id, 'confidence' => '5', 'risk' => '1'],
                ],
            ],
        ]);

        $this->actingAs($user)->patchJson(route('board.update'), $payload)->assertOk();

        $this->assertDatabaseHas('working_board_entries', [
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_MASTER,
            'player_id' => $player->id,
            'round_key' => '1',
        ]);
    }

    public function test_board_patch_rejects_duplicate_player_on_same_board(): void
    {
        $user = User::factory()->admin()->create();
        $a = Player::factory()->create(['player_pool' => 'hs']);

        $payload = $this->boardsPayload([
            WorkingBoardEntry::BOARD_MASTER => [
                '1' => [['player_id' => $a->id, 'confidence' => '', 'risk' => '']],
                '2' => [['player_id' => $a->id, 'confidence' => '', 'risk' => '']],
            ],
        ]);

        $this->actingAs($user)->patchJson(route('board.update'), $payload)->assertStatus(422);
    }

    public function test_board_patch_persists_tier_dividers_in_round_order(): void
    {
        $user = User::factory()->admin()->create();
        $a = Player::factory()->create(['player_pool' => 'hs', 'last_name' => 'Tier', 'first_name' => 'Top']);
        $b = Player::factory()->create(['player_pool' => 'hs', 'last_name' => 'Tier', 'first_name' => 'Bottom']);

        $payload = $this->boardsPayload([
            WorkingBoardEntry::BOARD_MASTER => [
                '1' => [
                    ['entry_type' => WorkingBoardEntry::ENTRY_TYPE_PLAYER, 'player_id' => $a->id, 'confidence' => '', 'risk' => ''],
                    ['entry_type' => WorkingBoardEntry::ENTRY_TYPE_TIER_DIVIDER],
                    ['entry_type' => WorkingBoardEntry::ENTRY_TYPE_PLAYER, 'player_id' => $b->id, 'confidence' => '', 'risk' => ''],
                ],
            ],
        ]);

        $this->actingAs($user)->patchJson(route('board.update'), $payload)->assertOk();

        $entries = WorkingBoardEntry::query()
            ->where('user_id', $user->id)
            ->where('board_type', WorkingBoardEntry::BOARD_MASTER)
            ->where('round_key', '1')
            ->orderBy('sort_order')
            ->get();

        $this->assertCount(3, $entries);
        $this->assertSame(WorkingBoardEntry::ENTRY_TYPE_PLAYER, $entries[0]->entry_type);
        $this->assertSame($a->id, $entries[0]->player_id);
        $this->assertSame(WorkingBoardEntry::ENTRY_TYPE_TIER_DIVIDER, $entries[1]->entry_type);
        $this->assertNull($entries[1]->player_id);
        $this->assertSame(WorkingBoardEntry::ENTRY_TYPE_PLAYER, $entries[2]->entry_type);
        $this->assertSame($b->id, $entries[2]->player_id);

        $this->actingAs($user)
            ->get(route('board.index'))
            ->assertOk()
            ->assertSee('"entry_type":"tier_divider"', false);
    }

    public function test_board_page_includes_master_players_and_non_targets_divider(): void
    {
        $user = User::factory()->admin()->create();
        $player = Player::factory()->create([
            'player_pool' => 'hs',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'position' => 'SS',
            'grade_role' => 5.5,
            'grade_swing' => 6,
        ]);

        WorkingBoardEntry::query()->create([
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_MASTER,
            'entry_type' => WorkingBoardEntry::ENTRY_TYPE_PLAYER,
            'player_id' => $player->id,
            'round_key' => '1',
            'sort_order' => 0,
            'confidence' => '4',
            'risk' => '2',
        ]);

        $html = $this->actingAs($user)->get(route('board.index'))->assertOk()->getContent();

        $this->assertStringContainsString('"label":"DOE, JANE"', $html);
        $this->assertStringContainsString('"player_id":'.$player->id, $html);
        $this->assertStringContainsString('Non-Targets', $html);
        $this->assertStringContainsString('working-board-non-target-divider', $html);
    }

    public function test_board_view_does_not_modify_database(): void
    {
        $user = User::factory()->admin()->create();
        $top = Player::factory()->create(['player_pool' => 'hs', 'last_name' => 'Alpha', 'first_name' => 'One']);
        $bottom = Player::factory()->create(['player_pool' => 'hs', 'last_name' => 'Beta', 'first_name' => 'Two']);

        WorkingBoardEntry::query()->create([
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_MASTER,
            'entry_type' => WorkingBoardEntry::ENTRY_TYPE_PLAYER,
            'player_id' => $top->id,
            'round_key' => '1',
            'sort_order' => 0,
            'confidence' => '5',
            'risk' => '1',
        ]);
        WorkingBoardEntry::query()->create([
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_MASTER,
            'entry_type' => WorkingBoardEntry::ENTRY_TYPE_TIER_DIVIDER,
            'player_id' => null,
            'round_key' => '1',
            'sort_order' => 1,
            'confidence' => null,
            'risk' => null,
        ]);
        WorkingBoardEntry::query()->create([
            'user_id' => $user->id,
            'board_type' => WorkingBoardEntry::BOARD_MASTER,
            'entry_type' => WorkingBoardEntry::ENTRY_TYPE_PLAYER,
            'player_id' => $bottom->id,
            'round_key' => '1',
            'sort_order' => 2,
            'confidence' => '3',
            'risk' => '4',
        ]);

        $before = WorkingBoardEntry::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get(['board_type', 'entry_type', 'player_id', 'round_key', 'sort_order', 'confidence', 'risk'])
            ->map(fn (WorkingBoardEntry $e) => $e->only(['board_type', 'entry_type', 'player_id', 'round_key', 'sort_order', 'confidence', 'risk']))
            ->all();

        $this->actingAs($user)->get(route('board.index'))->assertOk();

        $after = WorkingBoardEntry::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get(['board_type', 'entry_type', 'player_id', 'round_key', 'sort_order', 'confidence', 'risk'])
            ->map(fn (WorkingBoardEntry $e) => $e->only(['board_type', 'entry_type', 'player_id', 'round_key', 'sort_order', 'confidence', 'risk']))
            ->all();

        $this->assertSame($before, $after);
    }

    public function test_board_patch_rejects_ncaa_player_on_master_board_when_invalid(): void
    {
        $user = User::factory()->admin()->create();
        $p = Player::factory()->create(['player_pool' => 'ncaa']);

        $payload = $this->boardsPayload([
            WorkingBoardEntry::BOARD_MASTER => [
                '1' => [['player_id' => $p->id, 'confidence' => '', 'risk' => '']],
            ],
        ]);

        $this->actingAs($user)->patchJson(route('board.update'), $payload)->assertOk();
    }

    /**
     * @param  array<string, array<string, list<array<string, mixed>>>>  $roundOverrides
     * @return array{boards: array<string, array{rounds: array<string, list<array<string, mixed>>>>}}
     */
    private function boardsPayload(array $roundOverrides = []): array
    {
        $rounds = [];
        foreach (WorkingBoardEntry::BOARD_ROUND_KEYS as $rk) {
            $rounds[$rk] = [];
        }
        if (isset($roundOverrides[WorkingBoardEntry::BOARD_MASTER])) {
            foreach ($roundOverrides[WorkingBoardEntry::BOARD_MASTER] as $rk => $list) {
                $rounds[$rk] = $list;
            }
        }

        return [
            'boards' => [
                WorkingBoardEntry::BOARD_MASTER => ['rounds' => $rounds],
            ],
        ];
    }
}
