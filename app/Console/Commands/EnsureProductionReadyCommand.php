<?php

namespace App\Console\Commands;

use App\Support\SqliteDatabaseBootstrap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class EnsureProductionReadyCommand extends Command
{
    protected $signature = 'app:production-doctor
                            {--fix : Clear Laravel caches (optimize:clear) before checking}';

    protected $description = 'Check common production issues (caches, Vite build, DB, routes) and print fixes';

    /**
     * @var list<string>
     */
    private const REQUIRED_ROUTES = [
        'dashboard',
        'login',
        'ncaa-data-sources.index',
    ];

    public function handle(): int
    {
        if ($this->option('fix')) {
            $this->components->info('Clearing Laravel caches…');
            Artisan::call('optimize:clear');
            $this->line(trim(Artisan::output()));
        }

        $failures = 0;

        $manifest = public_path('build/manifest.json');
        if (! is_file($manifest)) {
            $failures++;
            $this->components->error('Missing Vite build: public/build/manifest.json');
            $this->line('  Fix: npm ci && npm run build');
        } else {
            $this->components->info('Vite manifest present.');
        }

        foreach (['bootstrap/cache', 'storage', 'storage/logs', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views'] as $dir) {
            $path = base_path($dir);
            if (! is_dir($path) || ! is_writable($path)) {
                $failures++;
                $this->components->error("Not writable: {$dir}");
                $this->line('  Fix: chmod -R ug+rwx storage bootstrap/cache');
            }
        }

        if ($failures === 0) {
            $this->components->info('Storage paths writable.');
        }

        $cacheStore = (string) config('cache.default');
        $sessionDriver = (string) config('session.driver');
        $this->line("  CACHE_STORE={$cacheStore}  SESSION_DRIVER={$sessionDriver}");

        $sqliteReady = true;
        if (config('database.default') === 'sqlite') {
            $sqlitePath = (string) config('database.connections.sqlite.database');

            if ($sqlitePath === '' || $sqlitePath === ':memory:') {
                $this->line('  DB_DATABASE=:memory: (tests)');
            } else {
                $this->line("  DB_DATABASE={$sqlitePath}");

                if (! SqliteDatabaseBootstrap::fileExists($sqlitePath)) {
                    $sqliteReady = false;
                    $failures++;
                    $this->components->error('SQLite database file is missing (login will 500).');
                    $this->line('  Fix: php artisan app:init-database');
                    $this->line('       then php artisan app:ensure-admin-user');
                } elseif (! is_readable($sqlitePath) || ! is_writable($sqlitePath)) {
                    $sqliteReady = false;
                    $failures++;
                    $this->components->error("SQLite database is not readable/writable: {$sqlitePath}");
                    $this->line('  Fix: sudo chown www-data:www-data '.$sqlitePath.' && sudo chmod 664 '.$sqlitePath);
                }
            }
        }

        if ($sqliteReady && $cacheStore === 'database' && ! Schema::hasTable('cache')) {
            $failures++;
            $this->components->error('CACHE_STORE=database but the cache table is missing (POST /login will 500).');
            $this->line('  Fix: php artisan migrate --force   OR set CACHE_STORE=file in .env and php artisan config:clear');
        }

        if ($sqliteReady && $sessionDriver === 'database' && ! Schema::hasTable('sessions')) {
            $failures++;
            $this->components->error('SESSION_DRIVER=database but the sessions table is missing.');
            $this->line('  Fix: php artisan migrate --force   OR set SESSION_DRIVER=file in .env and php artisan config:clear');
        }

        if ($sqliteReady) {
            try {
                DB::connection()->getPdo();
                $this->components->info('Database connection OK.');
            } catch (\Throwable $e) {
                $failures++;
                $this->components->error('Database connection failed: '.$e->getMessage());
            }
        }

        if ($sqliteReady && ! Schema::hasTable('users')) {
            $failures++;
            $this->components->error('Missing users table (login cannot work).');
            $this->line('  Fix: php artisan app:init-database');
        } elseif ($sqliteReady && Schema::hasTable('users') && ! Schema::hasColumn('users', 'is_admin')) {
            $failures++;
            $this->components->error('Missing column users.is_admin');
            $this->line('  Fix: php artisan app:init-database');
        }

        try {
            Cache::put('production-doctor-probe', 'ok', 10);
            if (Cache::pull('production-doctor-probe') !== 'ok') {
                throw new \RuntimeException('Cache read/write failed.');
            }
            $this->components->info('Cache store read/write OK.');
        } catch (\Throwable $e) {
            $failures++;
            $this->components->error('Cache store failed: '.$e->getMessage());
            $this->line('  Fix: set CACHE_STORE=file in .env, then php artisan config:clear');
        }

        $userCount = ($sqliteReady && Schema::hasTable('users')) ? (int) DB::table('users')->count() : 0;
        if ($sqliteReady && Schema::hasTable('users') && $userCount === 0) {
            $failures++;
            $this->components->error('No users in the database — every login will fail.');
            $this->line('  Fix: php artisan app:ensure-admin-user');
        } else {
            $this->components->info("Users table has {$userCount} account(s).");
        }

        $missingRoutes = [];
        foreach (self::REQUIRED_ROUTES as $name) {
            if (! Route::has($name)) {
                $missingRoutes[] = $name;
            }
        }

        if ($missingRoutes !== []) {
            $failures++;
            $this->components->error('Missing routes: '.implode(', ', $missingRoutes));
            $this->line('  Fix: php artisan optimize:clear');
        } else {
            $this->components->info('Required routes registered.');
        }

        if ($failures > 0) {
            $this->newLine();
            $this->warn('Production doctor found '.$failures.' problem(s).');
            $this->line('Recommended recovery from the app root:');
            $this->line('  git pull');
            $this->line('  composer install --no-dev --optimize-autoloader');
            $this->line('  php artisan optimize:clear');
            $this->line('  php artisan app:init-database');
            $this->line('  php artisan app:ensure-admin-user');
            $this->line('  php artisan config:clear   # after editing .env (use CACHE_STORE=file, SESSION_DRIVER=file)');

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Production doctor: all checks passed.');

        return self::SUCCESS;
    }
}
