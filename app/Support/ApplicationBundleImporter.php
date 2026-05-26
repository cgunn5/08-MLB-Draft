<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

final class ApplicationBundleImporter
{
    /**
     * @return array<string, mixed>
     */
    public function import(string $zipPath): array
    {
        if (! is_file($zipPath)) {
            throw new RuntimeException("Bundle file not found: {$zipPath}");
        }

        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP Zip extension is required to import an application bundle.');
        }

        $databasePath = ApplicationBundlePaths::resolveSqliteDatabasePath();
        $this->backupCurrentDatabase($databasePath);

        $tempDir = storage_path('app/application-import-'.uniqid('', true));
        if (! mkdir($tempDir, 0755, true) && ! is_dir($tempDir)) {
            throw new RuntimeException("Could not create temp directory: {$tempDir}");
        }

        try {
            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) {
                throw new RuntimeException('Could not open bundle zip.');
            }

            if (! $zip->extractTo($tempDir)) {
                throw new RuntimeException('Could not extract bundle zip.');
            }

            $zip->close();

            /** @var array<string, mixed>|null $manifest */
            $manifest = json_decode((string) file_get_contents($tempDir.'/manifest.json'), true);
            if (! is_array($manifest) || ($manifest['version'] ?? null) !== ApplicationBundlePaths::MANIFEST_VERSION) {
                throw new RuntimeException('Unsupported or missing bundle manifest.');
            }

            $bundleDb = $tempDir.'/database.sqlite';
            if (! is_file($bundleDb)) {
                throw new RuntimeException('Bundle is missing database.sqlite.');
            }

            DB::disconnect();
            File::ensureDirectoryExists(dirname($databasePath));
            if (! copy($bundleDb, $databasePath)) {
                throw new RuntimeException("Could not replace database at {$databasePath}");
            }
            @chmod($databasePath, 0664);

            $this->restoreUploadFiles($tempDir.'/uploads');

            Artisan::call('optimize:clear');
            ApplicationDatabaseBackupTrigger::maybeRun();

            return $manifest;
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    private function backupCurrentDatabase(string $databasePath): void
    {
        if (! is_file($databasePath)) {
            return;
        }

        $backupDir = storage_path('app/'.trim((string) config('database_backup.directory', 'database-backups'), '/'));
        SqliteDatabaseFileBackup::copyTo($databasePath, $backupDir, 'pre-import');
    }

    private function restoreUploadFiles(string $extractedUploadsDir): void
    {
        $targetDir = ApplicationBundlePaths::uploadsDirectory();
        File::ensureDirectoryExists($targetDir);

        if (is_dir($targetDir)) {
            foreach (File::files($targetDir) as $existing) {
                File::delete($existing->getPathname());
            }
        }

        if (! is_dir($extractedUploadsDir)) {
            return;
        }

        foreach (File::files($extractedUploadsDir) as $file) {
            $dest = $targetDir.DIRECTORY_SEPARATOR.$file->getFilename();
            if (! copy($file->getPathname(), $dest)) {
                throw new RuntimeException("Could not copy upload file to {$dest}");
            }
        }
    }
}
