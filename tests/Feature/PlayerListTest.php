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
        $user = User::factory()->create();
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
}
