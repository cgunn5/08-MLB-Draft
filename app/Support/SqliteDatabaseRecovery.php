<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;

final class SqliteDatabaseRecovery
{
    /**
     * @return list<string>
     */
    public static function backupDirectory(): array
    {
        $relative = trim((string) config('database_backup.directory', 'database-backups'), '/');
        $primary = storage_path('app/'.$relative);

        return array_values(array_unique([$primary]));
    }

    /**
     * @return list<string> Newest first
     */
    public static function listBackupFiles(): array
    {
        $files = [];
        foreach (self::backupDirectory() as $dir) {
            if (! is_dir($dir)) {
                continue;
            }
            foreach (glob($dir.DIRECTORY_SEPARATOR.'*.sqlite') ?: [] as $path) {
                if (is_file($path)) {
                    $files[] = $path;
                }
            }
        }

        usort($files, static fn (string $a, string $b): int => (int) filemtime($b) <=> (int) filemtime($a));

        return $files;
    }

    public static function latestBackupPath(): ?string
    {
        $files = self::listBackupFiles();

        return $files[0] ?? null;
    }

    public static function userCountInFile(string $path): ?int
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        try {
            $pdo = new PDO('sqlite:'.$path, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn();
            if ($tables === false) {
                return 0;
            }

            return (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function restoreLatestBackupTo(string $targetPath): ?string
    {
        foreach (self::listBackupFiles() as $backupPath) {
            if ((int) filesize($backupPath) < 64) {
                continue;
            }

            if (self::copyBackupToTarget($backupPath, $targetPath)) {
                return $backupPath;
            }
        }

        return null;
    }

    /**
     * When the live database has no users but a backup does, replace the live file.
     */
    public static function restoreLatestBackupIfLiveDatabaseIsEmpty(string $targetPath): ?string
    {
        if (! is_file($targetPath)) {
            return self::restoreLatestBackupTo($targetPath);
        }

        $liveUsers = self::userCountInFile($targetPath);
        if ($liveUsers === null || $liveUsers > 0) {
            return null;
        }

        foreach (self::listBackupFiles() as $backupPath) {
            if ($backupPath === $targetPath) {
                continue;
            }
            $backupUsers = self::userCountInFile($backupPath);
            if ($backupUsers === null || $backupUsers < 1) {
                continue;
            }

            if (self::copyBackupToTarget($backupPath, $targetPath)) {
                return $backupPath;
            }
        }

        return null;
    }

    private static function copyBackupToTarget(string $sourcePath, string $targetPath): bool
    {
        if (! is_readable($sourcePath)) {
            return false;
        }

        File::ensureDirectoryExists(dirname($targetPath));

        if (! @copy($sourcePath, $targetPath)) {
            return false;
        }

        @chmod($targetPath, 0664);

        return is_file($targetPath);
    }

    public static function describeBackup(?string $path): ?array
    {
        if ($path === null || ! is_file($path)) {
            return null;
        }

        $mtime = @filemtime($path);
        $users = self::userCountInFile($path);

        return [
            'path' => $path,
            'basename' => basename($path),
            'modified_at' => $mtime ? gmdate('c', $mtime) : null,
            'bytes' => (int) filesize($path),
            'user_count' => $users,
        ];
    }
}
