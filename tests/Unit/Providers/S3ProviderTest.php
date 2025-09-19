<?php

use InnoGE\LaravelRclone\Providers\S3Provider;

it('has correct driver name', function () {
    $provider = new S3Provider;

    expect($provider->getDriver())->toBe('s3');
});

it('builds correct environment variables', function () {
    $provider = new S3Provider;
    $config = [
        'driver' => 's3',
        'key' => 'test-key',
        'secret' => 'test-secret',
        'region' => 'eu-west-1',
        'bucket' => 'test-bucket',
    ];

    $env = $provider->buildEnvironment('s3_disk', $config);

    expect($env)
        ->toHaveKey('RCLONE_CONFIG_S3_DISK_TYPE', 's3')
        ->toHaveKey('RCLONE_CONFIG_S3_DISK_ACCESS_KEY_ID', 'test-key')
        ->toHaveKey('RCLONE_CONFIG_S3_DISK_SECRET_ACCESS_KEY', 'test-secret')
        ->toHaveKey('RCLONE_CONFIG_S3_DISK_REGION', 'eu-west-1')
        ->toHaveKey('RCLONE_CONFIG_S3_DISK_BUCKET', 'test-bucket');
});

it('handles optional endpoint configuration', function () {
    $provider = new S3Provider;
    $config = [
        'driver' => 's3',
        'key' => 'test-key',
        'secret' => 'test-secret',
        'region' => 'eu-west-1',
        'bucket' => 'test-bucket',
        'endpoint' => 'https://minio.example.com',
    ];

    $env = $provider->buildEnvironment('s3_disk', $config);

    expect($env)
        ->toHaveKey('RCLONE_CONFIG_S3_DISK_ENDPOINT', 'https://minio.example.com');
});

it('handles path style endpoint configuration', function () {
    $provider = new S3Provider;
    $config = [
        'driver' => 's3',
        'key' => 'test-key',
        'secret' => 'test-secret',
        'region' => 'eu-west-1',
        'bucket' => 'test-bucket',
        'use_path_style_endpoint' => true,
    ];

    $env = $provider->buildEnvironment('s3_disk', $config);

    expect($env)
        ->toHaveKey('RCLONE_CONFIG_S3_DISK_FORCE_PATH_STYLE', 'true');
});

it('uses default values when not specified', function () {
    $provider = new S3Provider;
    $config = [
        'driver' => 's3',
    ];

    $env = $provider->buildEnvironment('s3_disk', $config);

    expect($env)
        ->toHaveKey('RCLONE_CONFIG_S3_DISK_TYPE', 's3')
        ->toHaveKey('RCLONE_CONFIG_S3_DISK_ACCESS_KEY_ID', '')
        ->toHaveKey('RCLONE_CONFIG_S3_DISK_SECRET_ACCESS_KEY', '')
        ->toHaveKey('RCLONE_CONFIG_S3_DISK_REGION', 'us-east-1')
        ->toHaveKey('RCLONE_CONFIG_S3_DISK_BUCKET', '');
});
