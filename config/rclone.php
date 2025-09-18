<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rclone Binary Path
    |--------------------------------------------------------------------------
    |
    | Path to the rclone binary. If null, it will use the rclone binary
    | from the system PATH.
    |
    */
    'binary_path' => env('RCLONE_BINARY_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Default Timeout
    |--------------------------------------------------------------------------
    |
    | Default timeout for rclone processes in seconds.
    | Set to null for no timeout.
    |
    */
    'timeout' => env('RCLONE_TIMEOUT', 3600), // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Base Options
    |--------------------------------------------------------------------------
    |
    | Default rclone options that will be applied to all commands.
    | These can be overridden using the fluent API methods.
    |
    */
    'base_options' => [
        '--delete-after',
        '--fast-list',
        '--checksum',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    |
    | Default values for various rclone parameters.
    |
    */
    'defaults' => [
        'transfers' => 4,
        'checkers' => 8,
        'retries' => 3,
        'stat_interval' => 1,
        'progress' => false,
    ],
];
