<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Export storage disk
    |--------------------------------------------------------------------------
    |
    | Disk where generated citation exports (.docx / .pdf) are written. On
    | Laravel Cloud this should point at an Object Storage (s3) disk, since
    | managed queue workers have an ephemeral, unshared filesystem.
    |
    */

    'disk' => env('EXPORTS_DISK', env('FILESYSTEM_DISK', 'local')),

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Number of days a completed export stays downloadable before
    | `exports:prune` deletes the file and marks the record as expired.
    |
    */

    'retention_days' => (int) env('EXPORTS_RETENTION_DAYS', 14),

];
