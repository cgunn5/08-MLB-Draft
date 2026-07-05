<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_view_only_user_with_verified_email(): void
    {
        $this->artisan('app:ensure-user', [
            '--email' => 'viewer@example.com',
            '--password' => 'secret-pass',
            '--name' => 'View Only',
        ])->assertSuccessful();

        $user = User::query()->where('email', 'viewer@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->is_admin);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_admin_flag_grants_admin_access(): void
    {
        $this->artisan('app:ensure-user', [
            '--email' => 'editor@example.com',
            '--password' => 'secret-pass',
            '--admin' => true,
        ])->assertSuccessful();

        $user = User::query()->where('email', 'editor@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_admin);
    }
}
