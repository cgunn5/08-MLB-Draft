<?php

namespace App\Support;

use InvalidArgumentException;
use RuntimeException;

final class SqliteDatabaseFileBackup
{
    /**
     * Copy a SQLite database file into the backup directory with a timestamped name.
     *
     * @return string Absolute path to the new backup file
     */
    public static function copyTo(string $sourcePath, string $backupDirectory, string $filenamePrefix = 'database'): string
    {
        if ($sourcePath === '' || $sourcePath === ':memory:') {
            throw new InvalidArgumentException('SQLite backup requires a file path, not in-memory.');
        }

        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new InvalidArgumentException("SQLite database file is missing or unreadable: {$sourcePath}");
        }

        if (! is_dir($backupDirectory) && ! mkdir($backupDirectory, 0755, true) && ! is_dir($backupDirectory)) {
            throw new RuntimeException("Could not create backup directory: {$backupDirectory}");
        }

        $safePrefix = preg_match('/^[a-z0-9_-]+$/i', $filenamePrefix) === 1 ? $filenamePrefix : 'database';
        $dest = $backupDirectory.DIRECTORY_SEPARATOR.$safePrefix.'-'.gmdate('Y-m-d-His').'-'.uniqid('', true).'.sqlite';

        if (! @copy($sourcePath, $dest)) {
            throw new RuntimeException("Failed to copy SQLite database to {$dest}");
        }

        return $dest;
    }

    /**
     * Delete backup files under $backupDirectory matching any of $globPatterns that are older than $retentionDays.
     *
     * @param  array<int, string>  $globPatterns
     * @return int Number of files deleted
     */
    public static function pruneByAge(string $backupDirectory, int $retentionDays, array $globPatterns = ['*.sqlite', '*.sql.gz']): int
    {
        if ($retentionDays < 1 || ! is_dir($backupDirectory)) {
            return 0;
        }

        $cutoff = time() - ($retentionDays * 86400);
        $deleted = 0;

        foreach ($globPatterns as $pattern) {
            foreach (glob($backupDirectory.DIRECTORY_SEPARATOR.$pattern) ?: [] as $path) {
                if (! is_file($path)) {
                    continue;
                }
                $mtime = @filemtime($path);
                if ($mtime !== false && $mtime < $cutoff) {
                    if (@unlink($path)) {
                        $deleted++;
                    }
                }
            }
        }

        return $deleted;
    }
}
