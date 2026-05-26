<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureAdminUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_admin_user_with_verified_email(): void
    {
        $this->artisan('app:ensure-admin-user', [
            '--email' => 'admin@example.com',
            '--password' => 'secret-pass',
            '--name' => 'Admin User',
        ])->assertSuccessful();

        $user = User::query()->where('email', 'admin@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_admin);
        $this->assertNotNull($user->email_verified_at);
    }
}
