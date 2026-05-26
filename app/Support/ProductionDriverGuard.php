<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Prevents 500 errors when .env points session/cache at the database before migrations ran.
 */
final class ProductionDriverGuard
{
    public static function apply(): void
    {
        if (config('session.driver') === 'database') {
            config(['session.driver' => self::databaseTableExists('sessions') ? 'database' : 'file']);
        }

        if (config('cache.default') === 'database') {
            config(['cache.default' => self::databaseTableExists('cache') ? 'database' : 'file']);
        }
    }

    private static function databaseTableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
