<?php

namespace App\Console\Commands;

use App\Support\PersistentStorage;
use App\Support\SqliteDatabaseBootstrap;
use App\Support\SqliteDatabaseRecovery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ForgePersistenceDoctorCommand extends Command
{
    protected $signature = 'app:forge-persistence-doctor';

    protected $description = 'Verify SQLite and uploads live under shared storage (required for Laravel Forge redeploys)';

    public function handle(): int
    {
        $failures = 0;
        $dbPath = SqliteDatabaseBootstrap::configuredPath();

        $this->line('Storage path: '.storage_path());
        $this->line('Database path: '.$dbPath);
        $this->line('Uploads path: '.PersistentStorage::uploadsPath());

        if (! PersistentStorage::databasePathIsUnderSharedStorage($dbPath)) {
            $failures++;
            $this->components->error('DB_DATABASE must point inside storage/app/ on Forge.');
            $this->line('  Fix: remove DB_DATABASE from .env (uses '.PersistentStorage::databasePath().')');
            $this->line('  Never use database/database.sqlite on Forge — it is wiped every deploy.');
        } else {
            $this->components->info('Database path is under shared storage/.');
        }

        if (! is_file($dbPath)) {
            $failures++;
            $this->components->warn('Database file does not exist yet (first visit will create it).');
        } elseif (! is_readable($dbPath) || ! is_writable($dbPath)) {
            $failures++;
            $this->components->error('Database file is not readable/writable.');
            $this->line('  Fix: chmod 664 '.$dbPath);
        } else {
            $users = SqliteDatabaseRecovery::userCountInFile($dbPath);
            $this->components->info('Database file OK ('.number_format((int) filesize($dbPath)).' bytes, '.($users ?? '?').' user(s)).');
        }

        $uploads = PersistentStorage::uploadsPath();
        if (! is_dir($uploads) && ! mkdir($uploads, 0755, true) && ! is_dir($uploads)) {
            $failures++;
            $this->components->error('Could not create uploads directory: '.$uploads);
        } elseif (! is_writable($uploads)) {
            $failures++;
            $this->components->error('Uploads directory is not writable: '.$uploads);
        } else {
            $csvCount = count(glob($uploads.'/*.csv') ?: []);
            $this->components->info("Uploads directory OK ({$csvCount} CSV file(s)).");
        }

        if (Schema::hasTable('users')) {
            $count = (int) DB::table('users')->count();
            if ($count === 0) {
                $failures++;
                $this->components->error('No users in database — login will show one-time setup.');
            }
        }

        if ($failures > 0) {
            $this->newLine();
            $this->warn('Forge persistence doctor found '.$failures.' problem(s). See FORGE.md in the repo.');

            return self::FAILURE;
        }

        $this->components->info('Forge persistence: ready for redeploys without data loss.');

        return self::SUCCESS;
    }
}
