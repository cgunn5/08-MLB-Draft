<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstRunSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_login_when_no_users(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_login_page_is_shown_when_no_users(): void
    {
        $this->get('/login')->assertOk()->assertSee('Log in', false);
    }

    public function test_home_redirects_to_login_when_users_exist(): void
    {
        User::factory()->create();

        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_login_page_is_shown_when_users_exist(): void
    {
        User::factory()->create();

        $this->get('/login')->assertOk()->assertSee('Log in', false);
    }

    public function test_setup_creates_admin_and_loads_dashboard(): void
    {
        $response = $this->post(route('setup.store'), [
            'name' => 'C. Gunn',
            'email' => 'cgunn@texasrangers.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
            'load_players' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'cgunn@texasrangers.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_admin);
        $this->assertGreaterThan(0, Player::query()->count());
    }

    public function test_setup_is_unavailable_after_first_user_exists(): void
    {
        User::factory()->create();

        $this->get('/setup')->assertRedirect(route('login'));
    }
}
