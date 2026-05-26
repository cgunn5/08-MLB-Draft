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
        $connection = (string) config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver !== 'sqlite') {
            throw new InvalidArgumentException('Application bundle export requires a SQLite database connection.');
        }

        return SqliteDatabaseBootstrap::resolvePath(
            (string) config("database.connections.{$connection}.database")
        );
    }
}
