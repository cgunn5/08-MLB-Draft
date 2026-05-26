<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupApplicationDatabaseCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_command_skips_in_memory_sqlite_gracefully(): void
    {
        $this->artisan('app:backup-database', ['--no-prune' => true])
            ->expectsOutputToContain('Skipping backup')
            ->assertSuccessful();
    }

    public function test_backup_dry_run_succeeds(): void
    {
        $this->artisan('app:backup-database', ['--dry-run' => true])
            ->expectsOutputToContain('[dry-run]')
            ->assertSuccessful();
    }
}
