<?php

namespace App\Console\Commands;

use App\Support\SqliteDatabaseBootstrap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InitApplicationDatabaseCommand extends Command
{
    protected $signature = 'app:init-database
                            {--seed : Also run DatabaseSeeder (aggregate player list + default admin if missing)}';

    protected $description = 'Create the SQLite file when missing, then run migrations (required on first production deploy)';

    public function handle(): int
    {
        if (config('database.default') !== 'sqlite') {
            $this->components->warn('DB_CONNECTION is not sqlite; skipping file creation.');
        } else {
            $path = SqliteDatabaseBootstrap::configuredPath();
            $created = SqliteDatabaseBootstrap::ensureFileExists($path);
            if ($created) {
                $this->components->info("Created SQLite database: {$path}");
            } else {
                $this->components->info("SQLite database already exists: {$path}");
            }
        }

        $this->components->info('Running migrations…');
        Artisan::call('migrate', ['--force' => true]);
        $this->line(trim(Artisan::output()));

        if ($this->option('seed')) {
            $this->components->info('Seeding database…');
            Artisan::call('db:seed', ['--force' => true]);
            $this->line(trim(Artisan::output()));
        }

        $this->newLine();
        $this->components->info('Database ready.');
        $this->line('Next: php artisan app:ensure-admin-user');

        return self::SUCCESS;
    }
}
