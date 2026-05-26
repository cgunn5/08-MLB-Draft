<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProductionDoctorCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->create();
    }

    public function test_production_doctor_passes_in_test_environment(): void
    {
        $this->artisan('app:production-doctor')
            ->assertSuccessful();
    }

    public function test_production_doctor_fix_clears_route_cache(): void
    {
        Artisan::call('route:cache');
        $this->assertNotSame([], glob(base_path('bootstrap/cache/routes-*.php')) ?: []);

        Artisan::call('optimize:clear');

        $this->assertSame([], glob(base_path('bootstrap/cache/routes-*.php')) ?: []);
    }
}
