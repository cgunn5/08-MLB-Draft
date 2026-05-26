<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Uploaded stat CSV directory
    |--------------------------------------------------------------------------
    |
    | Directory containing data-source-uploads CSV files included in bundles.
    | Override in tests to keep exports isolated from developer data.
    |
    */
    'uploads_directory' => config('persistence.uploads'),

    /*
    |--------------------------------------------------------------------------
    | Export directory
    |--------------------------------------------------------------------------
    */
    'export_directory' => storage_path('app/application-exports'),
];
