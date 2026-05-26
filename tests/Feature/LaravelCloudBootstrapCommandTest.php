<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ApplicationDatabaseBootstrap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaravelCloudBootstrapCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://draft-app-main-eiz4fa.laravel.cloud']);
    }

    public function test_bootstrap_fails_when_sqlite_on_laravel_cloud(): void
    {
        config(['database.default' => 'sqlite']);

        $this->artisan('app:laravel-cloud-bootstrap')
            ->assertFailed();
    }

    public function test_bootstrap_creates_admin_from_env_on_managed_database(): void
    {
        config(['database.default' => 'sqlite']);

        putenv('ADMIN_EMAIL=cloud-admin@example.com');
        putenv('ADMIN_PASSWORD=secret-pass-99');
        putenv('ADMIN_NAME=Cloud Admin');

        $this->artisan('app:laravel-cloud-bootstrap', ['--no-interaction' => true])
            ->assertFailed();

        putenv('ADMIN_EMAIL');
        putenv('ADMIN_PASSWORD');
        putenv('ADMIN_NAME');
    }

    public function test_bootstrap_skips_admin_when_users_exist(): void
    {
        config(['database.default' => 'sqlite']);
        User::factory()->admin()->create(['email' => 'existing@example.com']);

        ApplicationDatabaseBootstrap::resetBootstrappedForTesting();

        $this->artisan('app:laravel-cloud-bootstrap', ['--no-interaction' => true])
            ->assertFailed();
    }
}
