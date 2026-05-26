<?php

namespace App\Support;

use RuntimeException;

final class SqliteDatabaseBootstrap
{
    public static function configuredPath(): string
    {
        $path = (string) config('database.connections.sqlite.database');

        if ($path === '' || $path === ':memory:') {
            throw new RuntimeException('SQLite backup and bootstrap require a file path, not in-memory.');
        }

        return $path;
    }

    public static function fileExists(?string $path = null): bool
    {
        $path = $path ?? self::configuredPath();

        return is_file($path);
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
