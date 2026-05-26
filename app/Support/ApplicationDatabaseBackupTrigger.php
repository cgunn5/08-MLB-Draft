<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

final class ApplicationDatabaseBackupTrigger
{
    public static function maybeRun(): void
    {
        if (app()->runningUnitTests() || config('database.default') !== 'sqlite') {
            return;
        }

        $cacheKey = 'application-database-backup-last-run';

        if (Cache::has($cacheKey)) {
            return;
        }

        try {
            Artisan::call('app:backup-database');
            Cache::put($cacheKey, now()->toIso8601String(), now()->addHour());
        } catch (\Throwable) {
            // Backup failure must not block login or setup.
        }
    }
}
