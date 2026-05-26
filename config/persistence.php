<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Persistent application data (survives Laravel Forge redeploys)
    |--------------------------------------------------------------------------
    |
    | Forge keeps the site-level storage/ directory between releases. All live
    | data (SQLite DB, CSV uploads, backups) must live under storage/app/.
    | Do not set DB_DATABASE to database/database.sqlite on Forge.
    |
    */
    'database' => storage_path('app/persistent/database.sqlite'),

    'uploads' => storage_path('app/private/data-source-uploads'),

    'backups' => storage_path('app/database-backups'),

    'installation_marker' => storage_path('app/persistent/.installation-complete'),

    /*
    | Used only by PHPUnit to exercise PersistentDatabaseConfig::apply().
    */
    'apply_in_tests' => env('PERSISTENCE_APPLY_IN_TESTS', false),

];
