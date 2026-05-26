<?php

namespace App\Console\Commands;

use App\Models\DataSourceUpload;
use App\Support\ApplicationBundleExporter;
use Illuminate\Console\Command;

class ExportApplicationBundleCommand extends Command
{
    protected $signature = 'app:export-application-bundle
                            {--output= : Absolute path for the zip file (default: storage/app/application-exports/)}';

    protected $description = 'Export the SQLite database and uploaded stat CSV files into a single zip for restoring on another server.';

    public function handle(ApplicationBundleExporter $exporter): int
    {
        try {
            $result = $exporter->export($this->option('output') ?: null);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $manifest = $result['manifest'];
        $this->info('Application bundle written: '.$result['path']);
        $this->line('Database size: '.$this->formatBytes((int) ($manifest['database_bytes'] ?? 0)));
        $this->line('Upload files: '.(int) ($manifest['upload_count'] ?? 0).' ('.$this->formatBytes((int) ($manifest['upload_bytes'] ?? 0)).')');

        $dbUploadCount = DataSourceUpload::query()->count();
        if ($dbUploadCount > (int) ($manifest['upload_count'] ?? 0)) {
            $missing = $dbUploadCount - (int) ($manifest['upload_count'] ?? 0);
            $this->warn("Database lists {$dbUploadCount} uploads but {$missing} CSV file(s) are missing on disk. Those stats will not work on live until the files are re-uploaded.");
        }

        $this->newLine();
        $this->comment('Upload this zip on the live app: Admin → Sync data → Restore from bundle.');

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }
}
