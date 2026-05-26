<?php

namespace App\Support;

/**
 * Laravel Cloud injects DB_* at runtime, but build-time config:cache may have baked
 * in sqlite as the default. Re-apply the live connection before any DB work.
 */
final class CloudDatabaseConfig
{
    public static function apply(): void
    {
        if (! HostedEnvironment::isLaravelCloud()) {
            return;
        }

        $connection = self::readEnv('DB_CONNECTION');
        $host = self::readEnv('DB_HOST');

        if ($host === '' || $host === '127.0.0.1') {
            return;
        }

        if ($connection === '' || $connection === 'sqlite') {
            $connection = str_contains($host, 'pg.laravel.cloud') ? 'pgsql' : 'mysql';
        }

        if (! in_array($connection, ['mysql', 'pgsql', 'mariadb'], true)) {
            return;
        }

        config(['database.default' => $connection]);
    }

    public static function managedDatabaseConfigured(): bool
    {
        $host = self::readEnv('DB_HOST');

        return $host !== '' && $host !== '127.0.0.1';
    }

    public static function readEnv(string $key): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        return is_string($value) ? trim($value) : '';
    }
}
