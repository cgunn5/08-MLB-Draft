<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class NcaaHardContactVisualStorage
{
    public const TYPE_PLATE = 'plate';

    public const TYPE_ZONE = 'zone';

    public static function disk(): string
    {
        return (string) config('data_source_uploads.disk', 'local');
    }

    public static function store(int $userId, int $playerId, string $type, UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        if (! in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            $ext = 'png';
        }

        $disk = self::disk();
        $path = sprintf(
            'hard-contact-visuals/%d/%d/%s.%s',
            $userId,
            $playerId,
            $type === self::TYPE_ZONE ? 'zone' : 'plate',
            $ext,
        );

        $stream = fopen($file->getRealPath(), 'r');
        if ($stream === false) {
            throw new \RuntimeException('Could not read uploaded image.');
        }

        try {
            Storage::disk($disk)->put($path, $stream);
        } finally {
            fclose($stream);
        }

        return ['disk' => $disk, 'path' => $path];
    }

    public static function delete(?string $disk, ?string $path): void
    {
        if ($disk === null || $path === null || $path === '') {
            return;
        }

        [$resolvedDisk, $resolvedPath] = DataSourceUploadStorage::resolveLocation($disk, $path);
        Storage::disk($resolvedDisk)->delete($resolvedPath);
    }

    public static function exists(?string $disk, ?string $path): bool
    {
        if ($disk === null || $path === null || $path === '') {
            return false;
        }

        return DataSourceUploadStorage::exists($disk, $path);
    }

    public static function mimeType(?string $disk, ?string $path): ?string
    {
        if ($disk === null || $path === null || $path === '') {
            return null;
        }

        [$resolvedDisk, $resolvedPath] = DataSourceUploadStorage::resolveLocation($disk, $path);

        return Storage::disk($resolvedDisk)->mimeType($resolvedPath);
    }

    public static function readStream(?string $disk, ?string $path)
    {
        if ($disk === null || $path === null || $path === '') {
            return null;
        }

        [$resolvedDisk, $resolvedPath] = DataSourceUploadStorage::resolveLocation($disk, $path);

        return Storage::disk($resolvedDisk)->readStream($resolvedPath);
    }

    public static function isValidType(string $type): bool
    {
        return in_array($type, [self::TYPE_PLATE, self::TYPE_ZONE], true);
    }

    public static function normalizeType(string $type): string
    {
        return Str::lower(trim($type));
    }
}
