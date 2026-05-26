<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

final class ApplicationBundleExporter
{
    /**
     * @return array{path: string, manifest: array<string, mixed>}
     */
    public function export(?string $destinationPath = null): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP Zip extension is required to export an application bundle.');
        }

        $databasePath = ApplicationBundlePaths::resolveSqliteDatabasePath();
        if (! is_file($databasePath)) {
            throw new RuntimeException("SQLite database file not found: {$databasePath}");
        }

        $uploadsDir = ApplicationBundlePaths::uploadsDirectory();
        $uploadFiles = is_dir($uploadsDir)
            ? collect(File::files($uploadsDir))->map(static fn ($file) => $file->getPathname())->all()
            : [];

        $exportDir = ApplicationBundlePaths::exportDirectory();
        if (! is_dir($exportDir) && ! mkdir($exportDir, 0755, true) && ! is_dir($exportDir)) {
            throw new RuntimeException("Could not create export directory: {$exportDir}");
        }

        $zipPath = $destinationPath ?? $exportDir.DIRECTORY_SEPARATOR.'mlb-draft-bundle-'.now()->format('Y-m-d-His').'.zip';

        $manifest = [
            'version' => ApplicationBundlePaths::MANIFEST_VERSION,
            'exported_at' => now()->toIso8601String(),
            'app' => (string) config('app.name'),
            'database_bytes' => filesize($databasePath),
            'upload_count' => count($uploadFiles),
            'upload_bytes' => array_sum(array_map(static fn (string $path): int => (int) filesize($path), $uploadFiles)),
        ];

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not create bundle zip: {$zipPath}");
        }

        $zip->addFile($databasePath, 'database.sqlite');

        foreach ($uploadFiles as $absolutePath) {
            $zip->addFile($absolutePath, 'uploads/'.basename($absolutePath));
        }

        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->close();

        return [
            'path' => $zipPath,
            'manifest' => $manifest,
        ];
    }
}
