<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
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

        try {
            DB::connection()->getPdo();
            $this->components->info('Database connection OK.');
        } catch (\Throwable $e) {
            $failures++;
            $this->components->error('Database connection failed: '.$e->getMessage());
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'is_admin')) {
            $failures++;
            $this->components->error('Missing column users.is_admin');
            $this->line('  Fix: php artisan migrate --force');
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
            $this->line('Recommended deploy from the app root:');
            $this->line('  git pull');
            $this->line('  composer install --no-dev --optimize-autoloader');
            $this->line('  php artisan app:production-doctor --fix');
            $this->line('  php artisan migrate --force');
            $this->line('  npm ci && npm run build');

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Production doctor: all checks passed.');

        return self::SUCCESS;
    }
}
