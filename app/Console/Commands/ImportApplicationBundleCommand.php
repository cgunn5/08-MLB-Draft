<?php

namespace App\Console\Commands;

use App\Support\ApplicationBundleImporter;
use Illuminate\Console\Command;

class ImportApplicationBundleCommand extends Command
{
    protected $signature = 'app:import-application-bundle {path : Absolute path to a bundle zip exported from app:export-application-bundle}';

    protected $description = 'Replace the local SQLite database and stat CSV uploads from an application bundle zip.';

    public function handle(ApplicationBundleImporter $importer): int
    {
        $path = (string) $this->argument('path');

        if (! $this->confirm('This replaces all player notes, grades, uploads metadata, and users with the bundle. Continue?', false)) {
            $this->warn('Import cancelled.');

            return self::SUCCESS;
        }

        try {
            $manifest = $importer->import($path);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Application bundle imported.');
        $this->line('Exported at: '.($manifest['exported_at'] ?? 'unknown'));
        $this->line('Upload files restored: '.(int) ($manifest['upload_count'] ?? 0));

        return self::SUCCESS;
    }
}
