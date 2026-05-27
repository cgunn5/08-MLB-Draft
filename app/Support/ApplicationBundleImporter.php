<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
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

            $this->backupCurrentDatabase();

            $targetDriver = config('database.connections.'.config('database.default').'.driver');

            if ($targetDriver === 'sqlite') {
                $databasePath = ApplicationBundlePaths::resolveSqliteDatabasePath();
                $this->importIntoSqliteDatabase($bundleDb, $databasePath);
            } else {
                $tableCounts = (new ApplicationBundleSqliteTableCopier)->copyFromSqliteFile($bundleDb);
                $manifest['imported_tables'] = $tableCounts;
            }

            $this->restoreUploadFiles($tempDir.'/uploads');
            DataSourceUploadStorage::retargetDatabaseUploadDisks();

            Artisan::call('optimize:clear');
            ApplicationDatabaseBackupTrigger::maybeRun();

            return $manifest;
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    private function importIntoSqliteDatabase(string $bundleDb, string $databasePath): void
    {
        DB::disconnect();
        File::ensureDirectoryExists(dirname($databasePath));
        if (! copy($bundleDb, $databasePath)) {
            throw new RuntimeException("Could not replace database at {$databasePath}");
        }
        @chmod($databasePath, 0664);
    }

    private function backupCurrentDatabase(): void
    {
        if (config('database.connections.'.config('database.default').'.driver') === 'sqlite') {
            $databasePath = ApplicationBundlePaths::resolveSqliteDatabasePath();
            if (! is_file($databasePath)) {
                return;
            }

            $backupDir = storage_path('app/'.trim((string) config('database_backup.directory', 'database-backups'), '/'));
            SqliteDatabaseFileBackup::copyTo($databasePath, $backupDir, 'pre-import');

            return;
        }

        try {
            Artisan::call('app:backup-database', ['--no-prune' => true]);
        } catch (\Throwable) {
            // Import should still proceed if mysqldump is unavailable on Cloud.
        }
    }

    private function restoreUploadFiles(string $extractedUploadsDir): void
    {
        if (! is_dir($extractedUploadsDir)) {
            return;
        }

        $disk = DataSourceUploadStorage::disk();
        $storage = Storage::disk($disk);

        foreach (File::files($extractedUploadsDir) as $file) {
            $relative = 'data-source-uploads/'.$file->getFilename();
            $storage->put($relative, (string) file_get_contents($file->getPathname()));
        }
    }
}
