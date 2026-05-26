<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaravelCloudSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://draft-app-main-eiz4fa.laravel.cloud',
            'database.default' => 'sqlite',
        ]);
    }

    public function test_setup_shows_database_required_instead_of_create_account_form(): void
    {
        $response = $this->get(route('setup'));

        $response->assertOk();
        $response->assertSee('Do not create another admin account', false);
        $response->assertDontSee('CREATE ACCOUNT & CONTINUE', false);
    }

    public function test_setup_store_is_rejected_on_laravel_cloud_sqlite(): void
    {
        $response = $this->post(route('setup.store'), [
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('setup'));
        $this->assertDatabaseCount('users', 0);
    }
}
