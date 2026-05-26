<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use RuntimeException;

final class SqliteDatabaseBootstrap
{
    public static function defaultPath(): string
    {
        return PersistentStorage::databasePath();
    }

    public static function legacyPath(): string
    {
        return database_path('database.sqlite');
    }

    /** @deprecated Previous default before persistent/ subdirectory */
    public static function previousDefaultPath(): string
    {
        return storage_path('app/database.sqlite');
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

    /**
     * @return list<string>
     */
    public static function existingCandidateSources(string $targetPath): array
    {
        $sources = [];

        foreach ([self::legacyPath(), self::previousDefaultPath(), self::defaultPath()] as $candidate) {
            if ($candidate === $targetPath || ! is_file($candidate)) {
                continue;
            }

            if ((int) filesize($candidate) < 1) {
                continue;
            }

            $sources[] = $candidate;
        }

        $latestBackup = SqliteDatabaseRecovery::latestBackupPath();
        if ($latestBackup !== null
            && $latestBackup !== $targetPath
            && ! in_array($latestBackup, $sources, true)) {
            $sources[] = $latestBackup;
        }

        return $sources;
    }

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

        if (SqliteDatabaseRecovery::restoreLatestBackupTo($targetPath) !== null) {
            return true;
        }

        return false;
    }

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

        if (ApplicationInstallationMarker::exists()) {
            SqliteDatabaseRecovery::restoreLatestBackupTo($path);
        }

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
