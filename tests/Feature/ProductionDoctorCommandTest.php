<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProductionDoctorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_doctor_passes_in_test_environment(): void
    {
        $this->artisan('app:production-doctor')
            ->assertSuccessful();
    }

    public function test_production_doctor_fix_clears_caches(): void
    {
        Artisan::call('route:cache');

        $this->artisan('app:production-doctor --fix')
            ->assertSuccessful();

        $this->assertSame([], glob(base_path('bootstrap/cache/routes-*.php')) ?: []);
    }
}
