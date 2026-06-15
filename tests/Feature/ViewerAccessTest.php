<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_open_board_ncaa_and_hs(): void
    {
        User::factory()->admin()->create();
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get('/board')
            ->assertOk();

        $this->actingAs($viewer)
            ->get('/ncaa')
            ->assertOk();

        $this->actingAs($viewer)
            ->get('/hs')
            ->assertOk();
    }

    public function test_viewer_cannot_open_dashboard(): void
    {
        User::factory()->admin()->create();
        $viewer = User::factory()->create();

        $this->actingAs($viewer)->get('/dashboard')->assertForbidden();
    }

    public function test_viewer_login_lands_on_board(): void
    {
        User::factory()->admin()->create();
        $viewer = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', [
            'email' => $viewer->email,
            'password' => 'password',
        ])->assertRedirect('/board');
    }

    public function test_viewer_navigation_only_shows_allowed_pages(): void
    {
        User::factory()->admin()->create();
        $viewer = User::factory()->create();

        $response = $this->actingAs($viewer)->get('/board');
        $response->assertOk();
        $response->assertSee('BOARD', false);
        $response->assertSee('NCAA Profiles', false);
        $response->assertSee('HS Profiles', false);
        $response->assertDontSee('HOME', false);
        $response->assertDontSee('PLAYERS', false);
        $response->assertDontSee('Notes/Grades', false);
    }

    public function test_viewer_cannot_open_notes_or_upload_routes(): void
    {
        User::factory()->admin()->create();
        $viewer = User::factory()->create();

        $this->actingAs($viewer)->get('/notes')->assertForbidden();
        $this->actingAs($viewer)->get('/data-sources')->assertForbidden();
        $this->actingAs($viewer)->get('/ncaa-data-sources')->assertForbidden();
        $this->actingAs($viewer)->get('/players')->assertForbidden();
    }

    public function test_viewer_cannot_update_board(): void
    {
        User::factory()->admin()->create();
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->patch('/board', ['rounds' => []])
            ->assertForbidden();
    }
}
