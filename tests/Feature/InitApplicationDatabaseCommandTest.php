<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InitApplicationDatabaseCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_init_database_creates_sqlite_file_when_missing(): void
    {
        $path = database_path('init-command-test.sqlite');

        if (File::exists($path)) {
            File::delete($path);
        }

        Config::set('database.connections.sqlite.database', $path);
        Config::set('database.default', 'sqlite');

        $this->artisan('app:init-database')
            ->assertSuccessful();

        $this->assertFileExists($path);

        File::delete($path);
    }
}
