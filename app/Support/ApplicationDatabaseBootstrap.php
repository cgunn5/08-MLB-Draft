<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

final class ApplicationDatabaseBootstrap
{
    private static bool $ran = false;

    /**
     * Create the SQLite file and run migrations when needed (first deploy / empty server).
     */
    public static function ensureReady(): void
    {
        if (self::$ran || app()->runningUnitTests()) {
            return;
        }

        self::$ran = true;

        if (config('database.default') !== 'sqlite') {
            return;
        }

        $path = (string) config('database.connections.sqlite.database');
        if ($path === '' || $path === ':memory:') {
            return;
        }

        $createdFile = false;
        if (! is_file($path)) {
            SqliteDatabaseBootstrap::ensureFileExists($path);
            $createdFile = true;
        }

        if ($createdFile || ! Schema::hasTable('migrations')) {
            self::runMigrationsOnce();
        }
    }

    public static function needsFirstRunSetup(): bool
    {
        self::ensureReady();

        try {
            return ! Schema::hasTable('users') || ! \App\Models\User::query()->exists();
        } catch (\Throwable) {
            return true;
        }
    }

    private static function runMigrationsOnce(): void
    {
        $lockPath = storage_path('framework/cache/db-bootstrap.lock');
        $lockDir = dirname($lockPath);
        if (! is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }

        $handle = fopen($lockPath, 'c+');
        if ($handle === false) {
            Artisan::call('migrate', ['--force' => true]);

            return;
        }

        try {
            if (flock($handle, LOCK_EX)) {
                if (! Schema::hasTable('migrations')) {
                    Artisan::call('migrate', ['--force' => true]);
                }
                flock($handle, LOCK_UN);
            }
        } finally {
            fclose($handle);
        }
    }
}
