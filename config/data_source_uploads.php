<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stat CSV upload disk
    |--------------------------------------------------------------------------
    |
    | HS/NCAA dataset CSV files. On Laravel Cloud, attach Object Storage and
    | set DATA_SOURCE_UPLOADS_DISK (or FILESYSTEM_DISK) to the bucket disk name.
    | Local development uses the "local" disk by default.
    |
    */
    'disk' => env('DATA_SOURCE_UPLOADS_DISK', env('FILESYSTEM_DISK', 'local')),

];
