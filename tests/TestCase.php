<?php

namespace Tests;

use App\Models\User;
use App\Support\ApplicationInstallationMarker;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function adminUser(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes);
    }

    protected function tearDown(): void
    {
        ApplicationInstallationMarker::clear();

        parent::tearDown();
    }
}
