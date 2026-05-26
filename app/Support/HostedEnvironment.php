<?php

namespace App\Support;

final class HostedEnvironment
{
    /**
     * True when the app runs on Laravel Cloud (*.laravel.cloud).
     */
    public static function isLaravelCloud(): bool
    {
        if (filter_var(env('LARAVEL_CLOUD', false), FILTER_VALIDATE_BOOL)) {
            return true;
        }

        if (isset($_SERVER['LARAVEL_CLOUD_DISK_CONFIG'])) {
            return true;
        }

        $url = strtolower((string) config('app.url', ''));

        return str_contains($url, '.laravel.cloud');
    }

    /**
     * SQLite cannot persist on Laravel Cloud — a hosted MySQL/Postgres database is required.
     */
    public static function laravelCloudSqliteMisconfiguration(): bool
    {
        return self::isLaravelCloud() && config('database.default') === 'sqlite';
    }
}
