<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ApplicationDatabaseBootstrap;
use App\Support\SqliteDatabaseFileBackup;
use App\Support\SqliteDatabaseRecovery;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseRecoveryTest extends TestCase
{
    private string $databasePath;

    private string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databasePath = storage_path('app/testing-recovery-'.uniqid('', true).'.sqlite');
        $this->backupDir = storage_path('app/testing-recovery-backups-'.uniqid('', true));

        config([
            'database.connections.sqlite.database' => $this->databasePath,
            'database_backup.directory' => str_replace(storage_path('app').'/', '', $this->backupDir),
        ]);

        ApplicationDatabaseBootstrap::resetBootstrappedForTesting();
        DB::purge('sqlite');
    }

    protected function tearDown(): void
    {
        ApplicationDatabaseBootstrap::resetBootstrappedForTesting();
        DB::purge('sqlite');
        File::delete($this->databasePath);
        File::deleteDirectory($this->backupDir);

        parent::tearDown();
    }

    public function test_setup_page_offers_restore_when_backup_has_users(): void
    {
        File::ensureDirectoryExists($this->backupDir);
        File::ensureDirectoryExists(dirname($this->databasePath));
        touch($this->databasePath);

        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);
        User::factory()->admin()->create(['email' => 'saved@example.com']);
        SqliteDatabaseFileBackup::copyTo($this->databasePath, $this->backupDir, 'database');

        File::delete($this->databasePath);
        touch($this->databasePath);
        ApplicationDatabaseBootstrap::resetBootstrappedForTesting();
        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);

        $response = $this->get(route('setup'));

        if ($response->isRedirect(route('login'))) {
            $this->assertDatabaseHas('users', ['email' => 'saved@example.com']);

            return;
        }

        $response->assertOk()->assertSee('Restore my data', false);
    }

    public function test_restore_from_backup_returns_to_login_with_existing_user(): void
    {
        File::ensureDirectoryExists($this->backupDir);
        File::ensureDirectoryExists(dirname($this->databasePath));
        touch($this->databasePath);

        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);
        User::factory()->admin()->create(['email' => 'saved@example.com']);
        SqliteDatabaseFileBackup::copyTo($this->databasePath, $this->backupDir, 'database');

        File::delete($this->databasePath);
        touch($this->databasePath);
        ApplicationDatabaseBootstrap::resetBootstrappedForTesting();
        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);

        $this->post(route('setup.restore-backup'))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', ['email' => 'saved@example.com']);
    }

    public function test_bootstrap_auto_recovers_empty_database_from_backup(): void
    {
        File::ensureDirectoryExists($this->backupDir);
        File::ensureDirectoryExists(dirname($this->databasePath));
        touch($this->databasePath);

        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);
        User::factory()->admin()->create(['email' => 'auto@example.com']);
        SqliteDatabaseFileBackup::copyTo($this->databasePath, $this->backupDir, 'database');

        File::delete($this->databasePath);
        touch($this->databasePath);
        ApplicationDatabaseBootstrap::resetBootstrappedForTesting();
        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);

        ApplicationDatabaseBootstrap::resetBootstrappedForTesting();
        DB::purge('sqlite');

        $restoredFrom = SqliteDatabaseRecovery::restoreLatestBackupIfLiveDatabaseIsEmpty($this->databasePath);
        $this->assertNotNull($restoredFrom);
        DB::purge('sqlite');
        $this->assertDatabaseHas('users', ['email' => 'auto@example.com']);
    }

    public function test_user_count_in_backup_file(): void
    {
        File::ensureDirectoryExists(dirname($this->databasePath));
        touch($this->databasePath);
        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);
        User::factory()->create();

        $this->assertSame(1, SqliteDatabaseRecovery::userCountInFile($this->databasePath));
    }
}
