<?php

namespace App\Console\Commands;

use App\Models\DataSourceUpload;
use App\Models\User;
use App\Support\CareerPgMasterUploadService;
use App\Support\DataSourceCsvFileStats;
use App\Support\DataSourceCsvHeaders;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class RecoverOrphanDataSourceUploads extends Command
{
    private const UUID_CSV = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.csv$/i';

    protected $signature = 'app:recover-orphan-data-source-uploads
                            {--user= : User id to attach rows to (default: first user)}
                            {--dry-run : List files that would be imported without writing}';

    protected $description = 'Recreate data_source_uploads rows for persisted CSVs (UUID filenames) still on disk after a DB reset. Does not restore notes, grades, heat rules, or profile slot settings.';

    public function handle(): int
    {
        $userId = $this->option('user');
        if ($userId !== null && $userId !== '') {
            $user = User::query()->find((int) $userId);
            if ($user === null) {
                $this->error("No user with id {$userId}.");

                return self::FAILURE;
            }
        } else {
            $user = User::query()->orderBy('id')->first();
            if ($user === null) {
                $this->error('No users exist. Create a user first.');

                return self::FAILURE;
            }
        }

        $disk = Storage::disk('local');
        $dir = $disk->path('data-source-uploads');
        if (! is_dir($dir)) {
            $this->warn('No data-source-uploads directory on the local disk.');

            return self::SUCCESS;
        }

        $existingPaths = DataSourceUpload::query()
            ->where('path', '!=', '')
            ->pluck('path')
            ->all();
        $existing = array_fill_keys($existingPaths, true);

        $dryRun = (bool) $this->option('dry-run');
        $would = 0;
        $skipped = 0;
        $failed = 0;

        foreach (scandir($dir) ?: [] as $basename) {
            if ($basename === '.' || $basename === '..') {
                continue;
            }
            if (! preg_match(self::UUID_CSV, $basename)) {
                continue;
            }

            $relativePath = 'data-source-uploads/'.$basename;
            if (isset($existing[$relativePath])) {
                $skipped++;

                continue;
            }

            $absolutePath = $dir.DIRECTORY_SEPARATOR.$basename;
            if (! is_file($absolutePath)) {
                continue;
            }

            try {
                $stats = DataSourceCsvFileStats::read($absolutePath);
            } catch (\Throwable $e) {
                $this->error("Unreadable CSV {$basename}: {$e->getMessage()}");
                $failed++;

                continue;
            }

            $portal = DataSourceCsvHeaders::guessDatasetPortal($stats['header_row']);
            $would++;

            if ($dryRun) {
                $this->line("[dry-run] {$basename} → portal={$portal}, rows={$stats['row_count']}");

                continue;
            }

            DataSourceUpload::query()->create([
                'user_id' => $user->id,
                'dataset_portal' => $portal,
                'upload_kind' => DataSourceUpload::UPLOAD_KIND_FILE,
                'name' => 'Recovered '.$basename,
                'original_filename' => $basename,
                'disk' => 'local',
                'path' => $relativePath,
                'header_row' => $stats['header_row'],
                'row_count' => $stats['row_count'],
            ]);
            $this->info("Recovered {$basename} ({$portal}, {$stats['row_count']} rows).");
        }

        if ($dryRun) {
            $this->info("Dry run: {$would} file(s) would be imported, {$skipped} already in DB.");
        } else {
            $this->info("Done. Imported {$would} file(s), skipped {$skipped} already linked, {$failed} failed.");
            Artisan::call('app:recover-hs-perfect-game-performance-slot', [
                '--user' => (string) $user->id,
            ]);
            $this->info('Checked HS uploads for Perfect Game (performance_pg) slot and synced career master when applicable.');
            CareerPgMasterUploadService::syncForUser($user);
        }

        $this->newLine();
        $this->comment('Player notes, grades, heat rules, browse settings, and profile feed checkboxes are NOT stored in these CSV files.');
        $this->comment('Restore those only from a backup of database/database.sqlite (e.g. Time Machine).');

        return self::SUCCESS;
    }
}
