<?php

declare(strict_types=1);

use InnoGE\LaravelRclone\Exceptions\InvalidConfigurationException;
use InnoGE\LaravelRclone\Providers\S3Provider;

test('validates all required fields are present', function () {
    $provider = new S3Provider;

    // Test missing key
    expect(fn () => $provider->validateConfiguration([
        'driver' => 's3',
        'secret' => 'test-secret',
        'region' => 'us-east-1',
        'bucket' => 'test-bucket',
    ]))->toThrow(InvalidConfigurationException::class);

    // Test missing secret
    expect(fn () => $provider->validateConfiguration([
        'driver' => 's3',
        'key' => 'test-key',
        'region' => 'us-east-1',
        'bucket' => 'test-bucket',
    ]))->toThrow(InvalidConfigurationException::class);

    // Test missing region
    expect(fn () => $provider->validateConfiguration([
        'driver' => 's3',
        'key' => 'test-key',
        'secret' => 'test-secret',
        'bucket' => 'test-bucket',
    ]))->toThrow(InvalidConfigurationException::class);

    // Test missing bucket
    expect(fn () => $provider->validateConfiguration([
        'driver' => 's3',
        'key' => 'test-key',
        'secret' => 'test-secret',
        'region' => 'us-east-1',
    ]))->toThrow(InvalidConfigurationException::class);
});

test('passes with valid configuration', function () {
    $provider = new S3Provider;

    // Should not throw exception
    $provider->validateConfiguration([
        'driver' => 's3',
        'key' => 'test-key',
        'secret' => 'test-secret',
        'region' => 'us-east-1',
        'bucket' => 'valid-bucket-123',
    ]);

    expect(true)->toBeTrue(); // If we get here, validation passed
});

test('builds environment with endpoint configuration', function () {
    $provider = new S3Provider;

    $env = $provider->buildEnvironment('s3_test', [
        'driver' => 's3',
        'key' => 'test-key',
        'secret' => 'test-secret',
        'region' => 'us-east-1',
        'bucket' => 'test-bucket',
        'endpoint' => 'https://custom-s3.example.com',
    ]);

    expect($env)->toHaveKey('RCLONE_CONFIG_S3_TEST_ENDPOINT');
    expect($env['RCLONE_CONFIG_S3_TEST_ENDPOINT'])->toBe('https://custom-s3.example.com');
});

test('builds environment with path style endpoint configuration', function () {
    $provider = new S3Provider;

    // Test with path style enabled
    $env = $provider->buildEnvironment('s3_test', [
        'driver' => 's3',
        'key' => 'test-key',
        'secret' => 'test-secret',
        'region' => 'us-east-1',
        'bucket' => 'test-bucket',
        'use_path_style_endpoint' => true,
    ]);

    expect($env)->toHaveKey('RCLONE_CONFIG_S3_TEST_FORCE_PATH_STYLE');
    expect($env['RCLONE_CONFIG_S3_TEST_FORCE_PATH_STYLE'])->toBe('true');

    // Test with path style disabled
    $env2 = $provider->buildEnvironment('s3_test', [
        'driver' => 's3',
        'key' => 'test-key',
        'secret' => 'test-secret',
        'region' => 'us-east-1',
        'bucket' => 'test-bucket',
        'use_path_style_endpoint' => false,
    ]);

    expect($env2)->toHaveKey('RCLONE_CONFIG_S3_TEST_FORCE_PATH_STYLE');
    expect($env2['RCLONE_CONFIG_S3_TEST_FORCE_PATH_STYLE'])->toBe('false');
});

test('builds remote path with bucket only', function () {
    $provider = new S3Provider;

    $config = [
        'driver' => 's3',
        'bucket' => 'foobar',
    ];

    $path = $provider->buildRemotePath('s3_test', 'backups', $config);

    expect($path)->toBe('s3_test:foobar/backups');
});

test('builds remote path with bucket and root', function () {
    $provider = new S3Provider;

    $config = [
        'driver' => 's3',
        'bucket' => 'foobar',
        'root' => 'quux',
    ];

    $path = $provider->buildRemotePath('s3_test', 'backups', $config);

    expect($path)->toBe('s3_test:foobar/quux/backups');
});

test('builds remote path with bucket and root with trailing slashes', function () {
    $provider = new S3Provider;

    $config = [
        'driver' => 's3',
        'bucket' => 'foobar',
        'root' => '/quux/',
    ];

    $path = $provider->buildRemotePath('s3_test', '/backups/', $config);

    expect($path)->toBe('s3_test:foobar/quux/backups');
});

test('builds remote path with empty path', function () {
    $provider = new S3Provider;

    $config = [
        'driver' => 's3',
        'bucket' => 'foobar',
        'root' => 'quux',
    ];

    $path = $provider->buildRemotePath('s3_test', '/', $config);

    expect($path)->toBe('s3_test:foobar/quux');
});

test('builds remote path without root', function () {
    $provider = new S3Provider;

    $config = [
        'driver' => 's3',
        'bucket' => 'foobar',
    ];

    $path = $provider->buildRemotePath('s3_test', '/', $config);

    expect($path)->toBe('s3_test:foobar');
});
