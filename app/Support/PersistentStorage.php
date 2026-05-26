<?php

namespace App\Support;

final class PersistentStorage
{
    public static function databasePath(): string
    {
        return (string) config('persistence.database');
    }

    public static function uploadsPath(): string
    {
        return (string) config('persistence.uploads');
    }

    public static function backupsPath(): string
    {
        return (string) config('persistence.backups');
    }

    /**
     * True when storage/ is a symlink (typical Laravel Forge layout).
     */
    public static function storageAppearsShared(): bool
    {
        $storage = storage_path();
        if (is_link($storage)) {
            return true;
        }

        $parent = realpath($storage.'/app/persistent') ?: $storage.'/app/persistent';

        return is_link($storage) || (is_dir($parent) && is_writable($parent));
    }

    public static function databasePathIsUnderSharedStorage(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);
        $persistentDir = str_replace('\\', '/', dirname(self::databasePath())).'/';

        return str_starts_with($normalized, $persistentDir);
    }
}
