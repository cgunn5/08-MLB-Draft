<?php

namespace App\Support;

use InvalidArgumentException;

final class ApplicationBundlePaths
{
    public const MANIFEST_VERSION = 1;

    public static function uploadsDirectory(): string
    {
        return (string) config('application_bundle.uploads_directory');
    }

    public static function exportDirectory(): string
    {
        return (string) config('application_bundle.export_directory');
    }

    public static function resolveSqliteDatabasePath(): string
    {
        if (config('database.default') !== 'sqlite') {
            throw new InvalidArgumentException('Application bundles only support SQLite databases.');
        }

        $path = (string) config('database.connections.sqlite.database');

        if ($path === '' || $path === ':memory:') {
            throw new InvalidArgumentException('SQLite database path is not configured for file backup.');
        }

        if ($path[0] !== '/' && ! preg_match('~^([A-Za-z]:[\\\\/])~', $path)) {
            $path = base_path($path);
        }

        return $path;
    }
}
