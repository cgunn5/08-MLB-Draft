<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HsDashboardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function hs_index_loads_when_placeholder_and_list_has_players(): void
    {
        $user = User::factory()->create();
        Player::factory()->create(['player_pool' => 'hs', 'first_name' => 'A', 'last_name' => 'B']);

        $response = $this->actingAs($user)->get(route('hs.index'));

        $response->assertOk();
    }
}
