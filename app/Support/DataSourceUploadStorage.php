<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * HS/NCAA CSV storage. Uses Laravel Object Storage (S3/R2) on Laravel Cloud so
 * uploads survive redeploys; local disk for development.
 */
final class DataSourceUploadStorage
{
    public static function disk(): string
    {
        return (string) config('data_source_uploads.disk', 'local');
    }

    public static function isRemote(string $disk): bool
    {
        return (string) config("filesystems.disks.{$disk}.driver") !== 'local';
    }

    public static function exists(string $disk, string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if (Storage::disk($disk)->exists($path)) {
            return true;
        }

        $configured = self::disk();
        if ($disk !== $configured && Storage::disk($configured)->exists($path)) {
            return true;
        }

        return false;
    }

    /**
     * Resolve which disk actually holds the file (handles legacy disk=local rows).
     *
     * @return array{0: string, 1: string}
     */
    public static function resolveLocation(string $disk, string $path): array
    {
        if ($path === '') {
            return [$disk, $path];
        }

        if (Storage::disk($disk)->exists($path)) {
            return [$disk, $path];
        }

        $configured = self::disk();
        if ($disk !== $configured && Storage::disk($configured)->exists($path)) {
            return [$configured, $path];
        }

        return [$disk, $path];
    }

    /**
     * Local filesystem path for reading/parsing CSV (downloads remote files to a temp copy).
     */
    public static function localPath(string $disk, string $path): string
    {
        [$disk, $path] = self::resolveLocation($disk, $path);

        if (! self::isRemote($disk)) {
            return Storage::disk($disk)->path($path);
        }

        $cached = self::tempCachePath($disk, $path);
        if (is_file($cached)) {
            return $cached;
        }

        $contents = Storage::disk($disk)->get($path);
        if ($contents === null) {
            throw new RuntimeException("Missing upload file: {$path}");
        }

        File::ensureDirectoryExists(dirname($cached));
        File::put($cached, $contents);

        return $cached;
    }

    /**
     * Push an edited local working copy back to remote storage when applicable.
     */
    public static function persist(string $disk, string $path, string $localPath): void
    {
        [$disk, $path] = self::resolveLocation($disk, $path);

        if (! self::isRemote($disk)) {
            return;
        }

        Storage::disk($disk)->put($path, (string) file_get_contents($localPath));
        self::clearTempCache($disk, $path);
    }

    public static function delete(string $disk, string $path): void
    {
        if ($path === '') {
            return;
        }

        [$resolvedDisk, $resolvedPath] = self::resolveLocation($disk, $path);
        Storage::disk($resolvedDisk)->delete($resolvedPath);
        self::clearTempCache($resolvedDisk, $resolvedPath);

        if ($resolvedDisk !== $disk) {
            Storage::disk($disk)->delete($path);
            self::clearTempCache($disk, $path);
        }
    }

    public static function putLocalFile(string $path, string $sourceLocalPath): void
    {
        $disk = self::disk();
        $stream = fopen($sourceLocalPath, 'r');
        if ($stream === false) {
            throw new RuntimeException("Could not read file for upload: {$sourceLocalPath}");
        }

        try {
            Storage::disk($disk)->put($path, $stream);
        } finally {
            fclose($stream);
        }
    }

    public static function retargetDatabaseUploadDisks(): int
    {
        $disk = self::disk();

        return (int) \Illuminate\Support\Facades\DB::table('data_source_uploads')
            ->where('upload_kind', '!=', \App\Models\DataSourceUpload::UPLOAD_KIND_CAREER_PG_MASTER)
            ->whereNotNull('path')
            ->where('path', '!=', '')
            ->update(['disk' => $disk]);
    }

    private static function tempCachePath(string $disk, string $path): string
    {
        return storage_path('framework/cache/csv-uploads/'.hash('sha256', $disk.'|'.$path).'.csv');
    }

    private static function clearTempCache(string $disk, string $path): void
    {
        $cached = self::tempCachePath($disk, $path);
        if (is_file($cached)) {
            File::delete($cached);
        }
    }
}
