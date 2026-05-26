<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use RuntimeException;

final class SqliteDatabaseBootstrap
{
    /**
     * Default SQLite location (under storage/ so deploys are less likely to wipe it).
     */
    public static function defaultPath(): string
    {
        return storage_path('app/database.sqlite');
    }

    /**
     * Older installs kept the file next to migrations.
     */
    public static function legacyPath(): string
    {
        return database_path('database.sqlite');
    }

    public static function configuredPath(): string
    {
        $path = (string) config('database.connections.sqlite.database');

        if ($path === '' || $path === ':memory:') {
            throw new RuntimeException('SQLite backup and bootstrap require a file path, not in-memory.');
        }

        return self::resolvePath($path);
    }

    public static function resolvePath(string $path): string
    {
        if ($path === '' || $path === ':memory:') {
            return $path;
        }

        if ($path[0] !== '/' && ! preg_match('~^([A-Za-z]:[\\\\/])~', $path)) {
            return base_path($path);
        }

        return $path;
    }

    public static function fileExists(?string $path = null): bool
    {
        $path = $path ?? self::configuredPath();

        return is_file($path);
    }

    /**
     * Existing database files that can be copied into $targetPath when it is missing.
     *
     * @return list<string>
     */
    public static function existingCandidateSources(string $targetPath): array
    {
        $sources = [];

        foreach ([self::legacyPath(), self::defaultPath()] as $candidate) {
            if ($candidate === $targetPath || ! is_file($candidate)) {
                continue;
            }

            if ((int) filesize($candidate) < 1) {
                continue;
            }

            $sources[] = $candidate;
        }

        return $sources;
    }

    /**
     * When the configured database file is missing, copy an older file from a known location.
     */
    public static function adoptExistingFileIfAvailable(?string $targetPath = null): bool
    {
        $targetPath = $targetPath ?? self::configuredPath();

        if (is_file($targetPath)) {
            return false;
        }

        foreach (self::existingCandidateSources($targetPath) as $source) {
            File::ensureDirectoryExists(dirname($targetPath));

            if (@copy($source, $targetPath)) {
                @chmod($targetPath, 0664);

                return true;
            }
        }

        return false;
    }

    /**
     * Create an empty SQLite file (and parent directory) when missing.
     */
    public static function ensureFileExists(?string $path = null): bool
    {
        $path = $path ?? self::configuredPath();

        if (is_file($path)) {
            return false;
        }

        self::adoptExistingFileIfAvailable($path);

        if (is_file($path)) {
            return false;
        }

        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Could not create database directory: {$directory}");
        }

        if (@touch($path) === false) {
            throw new RuntimeException("Could not create SQLite database file: {$path}");
        }

        @chmod($path, 0664);

        return true;
    }
}
