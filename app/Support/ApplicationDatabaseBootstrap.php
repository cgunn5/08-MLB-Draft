<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ApplicationDatabaseBootstrap
{
    private static bool $ran = false;

    public static function ensureReady(): void
    {
        if (self::$ran || app()->runningUnitTests()) {
            return;
        }

        self::$ran = true;

        if (config('database.default') !== 'sqlite') {
            return;
        }

        $path = self::configuredSqlitePath();
        if ($path === '' || $path === ':memory:') {
            return;
        }

        if (! PersistentStorage::databasePathIsUnderSharedStorage($path) && app()->environment('production')) {
            // Misconfigured production path — still attempt bootstrap but doctor will warn.
        }

        $createdFile = SqliteDatabaseBootstrap::ensureFileExists($path);

        if ($createdFile || ! Schema::hasTable('migrations')) {
            self::runMigrationsOnce();
        }

        self::recoverEmptyDatabaseFromBackupIfNeeded($path);
    }

    public static function needsFirstRunSetup(): bool
    {
        self::ensureReady();

        if (self::hasUsers()) {
            ApplicationInstallationMarker::mark();

            return false;
        }

        if (ApplicationInstallationMarker::exists()) {
            self::recoverEmptyDatabaseFromBackupIfNeeded(self::configuredSqlitePath());
            DB::purge('sqlite');

            if (self::hasUsers()) {
                return false;
            }
        }

        try {
            return ! Schema::hasTable('users') || ! \App\Models\User::query()->exists();
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function latestRecoverableBackupSummary(): ?array
    {
        foreach (SqliteDatabaseRecovery::listBackupFiles() as $path) {
            $users = SqliteDatabaseRecovery::userCountInFile($path);
            if ($users !== null && $users > 0) {
                return SqliteDatabaseRecovery::describeBackup($path);
            }
        }

        return null;
    }

    public static function restoreFromLatestRecoverableBackup(): ?array
    {
        if (config('database.default') !== 'sqlite') {
            throw new \RuntimeException('Database recovery only supports SQLite.');
        }

        $path = self::configuredSqlitePath();
        $restoredFrom = SqliteDatabaseRecovery::restoreLatestBackupTo($path);
        if ($restoredFrom === null) {
            return null;
        }

        DB::purge('sqlite');
        Artisan::call('optimize:clear');
        self::$ran = false;

        if (self::hasUsers()) {
            ApplicationInstallationMarker::mark();
        }

        return SqliteDatabaseRecovery::describeBackup($restoredFrom);
    }

    public static function configuredSqlitePath(): string
    {
        $path = (string) config('database.connections.sqlite.database');

        return SqliteDatabaseBootstrap::resolvePath($path);
    }

    public static function resetBootstrappedForTesting(): void
    {
        self::$ran = false;
    }

    private static function hasUsers(): bool
    {
        try {
            return Schema::hasTable('users') && \App\Models\User::query()->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    private static function recoverEmptyDatabaseFromBackupIfNeeded(string $path): void
    {
        $restoredFrom = SqliteDatabaseRecovery::restoreLatestBackupIfLiveDatabaseIsEmpty($path);
        if ($restoredFrom === null) {
            return;
        }

        DB::purge('sqlite');
        self::$ran = false;

        if (self::hasUsers()) {
            ApplicationInstallationMarker::mark();
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
