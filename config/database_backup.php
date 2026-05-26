<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scheduled backup (notes / grades live in the DB)
    |--------------------------------------------------------------------------
    |
    | Set APP_DB_BACKUP_SCHEDULE_ENABLED=false to skip the daily job (e.g. CI).
    | Cron on the server must run `php artisan schedule:run` every minute, or use
    | `php artisan schedule:work` under a process manager.
    |
    */
    'schedule_enabled' => env('APP_DB_BACKUP_SCHEDULE_ENABLED', true),

    'daily_at' => env('APP_DB_BACKUP_DAILY_AT', '03:15'),

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Files in the backup directory older than this many days are deleted after
    | each successful backup (unless --no-prune).
    |
    */
    'retention_days' => max(1, (int) env('APP_DB_BACKUP_RETENTION_DAYS', 30)),

    /*
    |--------------------------------------------------------------------------
    | Storage location (under storage/app/)
    |--------------------------------------------------------------------------
    */
    'directory' => env('APP_DB_BACKUP_DIRECTORY', 'database-backups'),

    /*
    |--------------------------------------------------------------------------
    | MySQL / MariaDB (optional)
    |--------------------------------------------------------------------------
    |
    | When DB_CONNECTION is mysql or mariadb, the command runs mysqldump if the
    | binary exists on PATH (or set MYSQLDUMP_PATH). Output is a .sql.gz file.
    |
    */
    'mysqldump_path' => env('MYSQLDUMP_PATH', 'mysqldump'),

];
