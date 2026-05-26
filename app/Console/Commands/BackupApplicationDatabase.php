<?php

namespace App\Console\Commands;

use App\Support\SqliteDatabaseFileBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\ExecutableFinder;

class BackupApplicationDatabase extends Command
{
    protected $signature = 'app:backup-database
                            {--dry-run : Show what would be backed up without writing files}
                            {--no-prune : Do not delete backups older than the retention period}';

    protected $description = 'Back up the application database (player notes, grades, uploads metadata, users). For SQLite (default), copies the DB file into storage/app/database-backups. For MySQL/MariaDB, runs mysqldump to a .sql.gz file when mysqldump is available.';

    public function handle(): int
    {
        $connectionName = config('database.default');
        $config = config("database.connections.{$connectionName}");
        $driver = is_array($config) ? ($config['driver'] ?? '') : '';

        $relativeDir = (string) config('database_backup.directory', 'database-backups');
        $backupDir = storage_path('app'.DIRECTORY_SEPARATOR.trim($relativeDir, DIRECTORY_SEPARATOR));

        if ($this->option('dry-run')) {
            $this->info('[dry-run] Would write backups under: '.$backupDir);
            $this->line('Driver: '.$driver.' (connection: '.$connectionName.')');

            return self::SUCCESS;
        }

        if (! is_dir($backupDir) && ! mkdir($backupDir, 0755, true) && ! is_dir($backupDir)) {
            $this->error('Could not create backup directory: '.$backupDir);

            return self::FAILURE;
        }

        try {
            match ($driver) {
                'sqlite' => $this->backupSqlite($config, $backupDir),
                'mysql', 'mariadb' => $this->backupMysqlFamily($driver, $config, $backupDir),
                default => $this->failUnsupported($driver),
            };
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (! $this->option('no-prune')) {
            $pruned = SqliteDatabaseFileBackup::pruneByAge(
                $backupDir,
                (int) config('database_backup.retention_days', 30),
            );
            if ($pruned > 0) {
                $this->comment("Pruned {$pruned} backup file(s) older than retention.");
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function backupSqlite(array $config, string $backupDir): void
    {
        $path = (string) ($config['database'] ?? '');

        if ($path === ':memory:' || $path === '') {
            $this->warn('Skipping backup: SQLite is in-memory or has no path (nothing to copy).');

            return;
        }

        $resolved = $path;
        if ($resolved !== '' && $resolved[0] !== '/' && ! preg_match('~^([A-Za-z]:[\\\\/])~', $resolved)) {
            $resolved = base_path($resolved);
        }

        $dest = SqliteDatabaseFileBackup::copyTo($resolved, $backupDir, 'database');
        $this->info('SQLite backup written: '.$dest);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function backupMysqlFamily(string $driver, array $config, string $backupDir): void
    {
        $binary = (string) config('database_backup.mysqldump_path', 'mysqldump');
        if ((new ExecutableFinder)->find($binary) === null) {
            $this->error(
                "mysqldump binary not found (looked for \"{$binary}\"). Install the MySQL client tools or set MYSQLDUMP_PATH in .env, ".
                'or export SQL using your host control panel.'
            );

            throw new \RuntimeException('mysqldump not available');
        }

        $database = (string) ($config['database'] ?? '');
        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (string) ($config['port'] ?? '3306');
        $user = (string) ($config['username'] ?? 'root');
        $password = (string) ($config['password'] ?? '');

        $timestamp = now()->format('Y-m-d-His-v');
        $outFile = $backupDir.DIRECTORY_SEPARATOR."database-{$timestamp}.sql.gz";

        $args = [
            $binary,
            '--single-transaction',
            '--quick',
            '--no-tablespaces',
            '-h', $host,
            '-P', $port,
            '-u', $user,
            $database,
        ];

        $env = [];
        if ($password !== '') {
            $env['MYSQL_PWD'] = $password;
        }

        $dump = Process::timeout(3600)
            ->env($env)
            ->run($args);

        if (! $dump->successful()) {
            $this->error(trim($dump->errorOutput() ?: $dump->output()));

            throw new \RuntimeException("mysqldump failed with exit code {$dump->exitCode()}");
        }

        $gz = gzencode($dump->output(), 9);
        if ($gz === false) {
            throw new \RuntimeException('Could not gzip mysqldump output.');
        }

        File::put($outFile, $gz);
        $this->info("{$driver} backup written: {$outFile}");
    }

    private function failUnsupported(string $driver): void
    {
        $this->error("Automatic backup is not implemented for driver \"{$driver}\". Use your host’s backup tools or export SQL manually.");

        throw new \RuntimeException('Unsupported database driver');
    }
}
