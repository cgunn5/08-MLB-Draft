<?php

namespace Tests\Unit;

use App\Support\HostedEnvironment;
use Tests\TestCase;

class HostedEnvironmentTest extends TestCase
{
    public function test_detects_laravel_cloud_from_app_url(): void
    {
        config(['app.url' => 'https://draft-app-main-eiz4fa.laravel.cloud']);

        $this->assertTrue(HostedEnvironment::isLaravelCloud());
    }

    public function test_does_not_detect_localhost_as_laravel_cloud(): void
    {
        config(['app.url' => 'http://127.0.0.1:8000']);

        $this->assertFalse(HostedEnvironment::isLaravelCloud());
    }
}
