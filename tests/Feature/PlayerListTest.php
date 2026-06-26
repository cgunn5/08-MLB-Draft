<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use App\Support\PlayerProfileCompleteness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerListTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_delete_a_player(): void
    {
        $user = User::factory()->admin()->create();
        $player = Player::factory()->create();

        $response = $this->actingAs($user)->delete(route('players.destroy', $player));

        $response->assertRedirect(route('players.index'));
        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('players', ['id' => $player->id]);
    }

    public function test_guest_cannot_delete_a_player(): void
    {
        $player = Player::factory()->create();

        $this->delete(route('players.destroy', $player))->assertRedirect(route('login'));

        $this->assertDatabaseHas('players', ['id' => $player->id]);
    }

    public function test_authenticated_user_can_patch_player_names(): void
    {
        $user = User::factory()->admin()->create();
        $player = Player::factory()->create([
            'first_name' => 'Bo',
            'last_name' => 'Jackson',
            'player_pool' => 'ncaa',
            'school' => 'Auburn',
            'source_ranks' => ['model' => 10, 'mlb' => 20, 'fangraphs' => 44],
        ]);

        $response = $this->actingAs($user)->patchJson(route('players.update', $player), [
            'first_name' => 'Ben',
            'last_name' => 'Jackson',
            'player_pool' => 'hs',
            'school' => 'Texas',
        ]);

        $response->assertOk();
        $response->assertJsonPath('row.first_name', 'Ben');
        $response->assertJsonPath('row.name', 'JACKSON, BEN');
        $response->assertJsonPath('row.player_pool', 'HS');
        $response->assertJsonPath('row.player_pool_key', 'hs');
        $response->assertJsonPath('row.school', 'Texas');

        $player->refresh();
        $this->assertSame('Ben', $player->first_name);
        $this->assertSame('hs', $player->player_pool);
        $this->assertSame('Texas', $player->school);
        $this->assertSame(['model' => 10, 'mlb' => 20, 'fangraphs' => 44], $player->source_ranks);
    }

    public function test_guest_cannot_patch_player_list(): void
    {
        $player = Player::factory()->create();

        $this->patchJson(route('players.update', $player), [
            'first_name' => 'X',
            'last_name' => 'Y',
            'player_pool' => 'ncaa',
            'school' => null,
        ])->assertUnauthorized();
    }

    public function test_player_list_marks_profile_complete_when_notes_and_grades_are_filled(): void
    {
        $user = User::factory()->admin()->create();
        Player::factory()->create([
            'first_name' => 'Will',
            'last_name' => 'Brick',
            'player_pool' => 'hs',
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
        Player::factory()->create([
            'first_name' => 'Incomplete',
            'last_name' => 'Player',
            'player_pool' => 'hs',
        ]);

        $response = $this->actingAs($user)->get(route('players.index'));

        $response->assertOk();
        $response->assertSee('player-list-config', false);
        $response->assertSee('playerListTable()', false);
        $this->assertTrue(PlayerProfileCompleteness::isComplete(
            Player::query()->where('last_name', 'Brick')->firstOrFail(),
        ));
    }

    public function test_player_list_table_includes_grade_and_board_columns(): void
    {
        $user = User::factory()->admin()->create();
        $player = Player::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'player_pool' => 'ncaa',
            'school' => 'Texas',
            'grade_role' => 6,
            'grade_perf' => 5.5,
            'grade_approach' => 6,
            'grade_damage' => 5.5,
            'grade_adj' => 5,
            'grade_contact' => 4.5,
            'grade_swing' => 6,
        ]);

        \App\Models\WorkingBoardEntry::query()->create([
            'user_id' => $user->id,
            'board_type' => 'master',
            'entry_type' => 'player',
            'player_id' => $player->id,
            'round_key' => '1',
            'sort_order' => 0,
            'confidence' => '4',
            'risk' => '2',
        ]);

        $response = $this->actingAs($user)->get(route('players.index'));

        $response->assertOk();
        $response->assertSee('player-list-config', false);
        $response->assertSee('playerListTable()', false);
        $response->assertSee('Then by', false);
        $response->assertSee('DOE, JANE', false);
        $response->assertSee('role_display', false);
        $response->assertSee('conf_display', false);
        $response->assertSee('risk_display', false);
        $response->assertSee('M-H', false);
        $response->assertSee('perf_display', false);
        $response->assertSee('damage_display', false);
        $response->assertSee('platoon_display', false);
    }

    public function test_player_list_table_shows_dash_for_hs_platoon(): void
    {
        $user = User::factory()->admin()->create();
        Player::factory()->create([
            'first_name' => 'Sam',
            'last_name' => 'Smith',
            'player_pool' => 'hs',
            'grade_contact' => 5.5,
        ]);

        $response = $this->actingAs($user)->get(route('players.index'));

        $response->assertOk();
        $response->assertSee('adj_display', false);
        $response->assertSee('platoon_display', false);
        $response->assertSee('SMITH, SAM', false);
    }

    public function test_authenticated_user_can_add_player_with_source_ranks(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->post(route('players.store'), [
            'first_name' => 'Casey',
            'last_name' => 'Jones',
            'player_pool' => 'ncaa',
            'source_mdl' => 1,
            'source_ba' => 50,
        ])->assertRedirect(route('players.index'));

        $player = Player::query()->where('last_name', 'Jones')->first();
        $this->assertNotNull($player);
        $this->assertSame(['model' => 1, 'ba' => 50], $player->source_ranks);
    }
}
