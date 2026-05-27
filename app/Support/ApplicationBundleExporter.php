<?php

namespace App\Support;

use App\Models\DataSourceUpload;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
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

        $uploadFiles = $this->collectUploadFilesForExport();

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
            'upload_bytes' => array_sum(array_map(static fn (array $file): int => (int) ($file['bytes'] ?? 0), $uploadFiles)),
        ];

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not create bundle zip: {$zipPath}");
        }

        $zip->addFile($databasePath, 'database.sqlite');

        foreach ($uploadFiles as $file) {
            $zip->addFromString('uploads/'.$file['basename'], $file['contents']);
        }

        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->close();

        return [
            'path' => $zipPath,
            'manifest' => $manifest,
        ];
    }

    /**
     * @return list<array{basename: string, contents: string, bytes: int}>
     */
    private function collectUploadFilesForExport(): array
    {
        /** @var array<string, array{basename: string, contents: string, bytes: int}> $byBasename */
        $byBasename = [];

        DataSourceUpload::query()
            ->where('upload_kind', '!=', DataSourceUpload::UPLOAD_KIND_CAREER_PG_MASTER)
            ->whereNotNull('path')
            ->where('path', '!=', '')
            ->orderBy('id')
            ->get(['disk', 'path'])
            ->each(function (DataSourceUpload $upload) use (&$byBasename): void {
                if (! DataSourceUploadStorage::exists($upload->disk, $upload->path)) {
                    return;
                }

                [$disk, $path] = DataSourceUploadStorage::resolveLocation($upload->disk, $upload->path);
                $contents = Storage::disk($disk)->get($path);
                if ($contents === null) {
                    return;
                }

                $basename = basename($path);
                $byBasename[$basename] = [
                    'basename' => $basename,
                    'contents' => $contents,
                    'bytes' => strlen($contents),
                ];
            });

        $uploadsDir = ApplicationBundlePaths::uploadsDirectory();
        if (is_dir($uploadsDir)) {
            foreach (File::files($uploadsDir) as $file) {
                $basename = $file->getFilename();
                if (isset($byBasename[$basename])) {
                    continue;
                }

                $contents = (string) file_get_contents($file->getPathname());
                $byBasename[$basename] = [
                    'basename' => $basename,
                    'contents' => $contents,
                    'bytes' => strlen($contents),
                ];
            }
        }

        return array_values($byBasename);
    }
}
