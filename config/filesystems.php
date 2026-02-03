<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Specify the default filesystem disk that should be used by the framework.
    |
    */

    'default' => env('FILESYSTEM_DISK', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Configure as many filesystem "disks" as you wish. Laravel supports
    | "local", "ftp", "sftp", "s3". We're adding Backblaze B2 as an S3 disk.
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        // Standard S3 (AWS)
      's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'), // optional, if you use custom endpoint
        'use_path_style_endpoint' => false,
    ],


        // // Backblaze B2 (S3-compatible)
        // 'b2' => [
        //     'driver' => 's3',
        //     'key' => env('B2_ACCOUNT_ID'),                  // B2 KeyID
        //     'secret' => env('B2_APPLICATION_KEY'),         // B2 Application Key
        //     'region' => env('B2_REGION', 'us-east-005'),   // B2 bucket region
        //     'bucket' => env('B2_BUCKET'),                  // Your bucket name
        //     'endpoint' => env('B2_ENDPOINT', 'https://s3.us-east-005.backblazeb2.com'),
        //     'use_path_style_endpoint' => true,             // Required for B2
        //     'visibility' => 'public',                      // Makes uploaded files publicly accessible
        //     'throw' => false,
        // ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Configure symbolic links created by `php artisan storage:link`.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
