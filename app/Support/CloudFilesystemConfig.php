<?php

namespace App\Support;

/**
 * Applies Laravel Cloud injected object-storage disks at runtime.
 */
final class CloudFilesystemConfig
{
    public static function apply(): void
    {
        if (! isset($_SERVER['LARAVEL_CLOUD_DISK_CONFIG'])) {
            return;
        }

        $disks = json_decode((string) $_SERVER['LARAVEL_CLOUD_DISK_CONFIG'], true);
        if (! is_array($disks)) {
            return;
        }

        foreach ($disks as $disk) {
            if (! is_array($disk) || ! isset($disk['disk'])) {
                continue;
            }

            config([
                'filesystems.disks.'.$disk['disk'] => [
                    'driver' => 's3',
                    'key' => $disk['access_key_id'] ?? null,
                    'secret' => $disk['access_key_secret'] ?? null,
                    'bucket' => $disk['bucket'] ?? null,
                    'url' => $disk['url'] ?? null,
                    'endpoint' => $disk['endpoint'] ?? null,
                    'region' => 'auto',
                    'use_path_style_endpoint' => false,
                    'throw' => false,
                    'report' => false,
                ],
            ]);

            if ($disk['is_default'] ?? false) {
                config(['filesystems.default' => $disk['disk']]);
                if (! env('DATA_SOURCE_UPLOADS_DISK')) {
                    config(['data_source_uploads.disk' => $disk['disk']]);
                }
            }
        }
    }
}
