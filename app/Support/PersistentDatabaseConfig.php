<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Forces SQLite onto the persistent storage path on every boot so production
 * survives redeploys even when .env still points at database/database.sqlite.
 */
final class PersistentDatabaseConfig
{
    public static function apply(): void
    {
        if ((app()->runningUnitTests() && ! config('persistence.apply_in_tests')) || config('database.default') !== 'sqlite') {
            return;
        }

        if (HostedEnvironment::isLaravelCloud()) {
            return;
        }

        $persistent = PersistentStorage::databasePath();
        $configured = SqliteDatabaseBootstrap::resolvePath(
            (string) config('database.connections.sqlite.database')
        );

        if ($configured === $persistent) {
            return;
        }

        if (! PersistentStorage::databasePathIsUnderSharedStorage($configured)) {
            self::migrateDataToPersistentPath($configured, $persistent);
            config(['database.connections.sqlite.database' => $persistent]);
            DB::purge('sqlite');
        }
    }

    private static function migrateDataToPersistentPath(string $configured, string $persistent): void
    {
        File::ensureDirectoryExists(dirname($persistent));

        if (is_file($configured) && (int) filesize($configured) > 0) {
            if (! is_file($persistent) || (int) filesize($persistent) < (int) filesize($configured)) {
                @copy($configured, $persistent);
                @chmod($persistent, 0664);
            }

            return;
        }

        SqliteDatabaseBootstrap::adoptExistingFileIfAvailable($persistent);
    }
}
