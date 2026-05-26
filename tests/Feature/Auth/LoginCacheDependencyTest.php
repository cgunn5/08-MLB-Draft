<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LoginCacheDependencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_post_500s_when_database_cache_store_has_no_cache_table(): void
    {
        config(['cache.default' => 'database']);
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');

        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertServerError();
    }

    public function test_login_succeeds_with_file_cache_without_cache_table(): void
    {
        config(['cache.default' => 'file']);
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');

        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }
}
