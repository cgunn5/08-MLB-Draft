<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

final class ApplicationInstallationMarker
{
    public static function path(): string
    {
        if (app()->environment('testing')) {
            return storage_path('framework/testing/installation-complete');
        }

        return (string) config('persistence.installation_marker');
    }

    public static function mark(): void
    {
        $path = self::path();
        File::ensureDirectoryExists(dirname($path));
        File::put($path, now()->toIso8601String());
    }

    public static function exists(): bool
    {
        return is_file(self::path());
    }

    public static function clear(): void
    {
        $path = self::path();
        if (is_file($path)) {
            File::delete($path);
        }
    }
}
