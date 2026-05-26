<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
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

    public function test_authenticated_user_can_patch_player_names_and_source_ranks(): void
    {
        $user = User::factory()->admin()->create();
        $player = Player::factory()->create([
            'first_name' => 'Bo',
            'last_name' => 'Jackson',
            'source_ranks' => ['model' => 10, 'mlb' => 20, 'fangraphs' => 44],
        ]);

        $response = $this->actingAs($user)->patchJson(route('players.update', $player), [
            'first_name' => 'Ben',
            'last_name' => 'Jackson',
            'source_mdl' => null,
            'source_mlb' => 5,
            'source_espn' => null,
            'source_law' => null,
            'source_fb' => null,
            'source_ba' => 99,
        ]);

        $response->assertOk();
        $response->assertJsonPath('row.first_name', 'Ben');
        $response->assertJsonPath('row.mdl', null);
        $response->assertJsonPath('row.mlb', 5);
        $response->assertJsonPath('row.fb', null);
        $response->assertJsonPath('row.ba', 99);

        $player->refresh();
        $this->assertSame(['mlb' => 5, 'ba' => 99], $player->source_ranks);
    }

    public function test_guest_cannot_patch_player_list(): void
    {
        $player = Player::factory()->create();

        $this->patchJson(route('players.update', $player), [
            'first_name' => 'X',
            'last_name' => 'Y',
            'source_mdl' => null,
            'source_mlb' => null,
            'source_espn' => null,
            'source_law' => null,
            'source_fb' => null,
            'source_ba' => null,
        ])->assertUnauthorized();
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
