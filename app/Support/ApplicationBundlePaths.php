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

        return SqliteDatabaseBootstrap::configuredPath();
    }
}
