<?php

use InnoGE\LaravelRclone\Providers\LocalProvider;

it('has correct driver name', function () {
    $provider = new LocalProvider;

    expect($provider->getDriver())->toBe('local');
});

it('builds correct environment variables', function () {
    $provider = new LocalProvider;
    $config = [
        'driver' => 'local',
        'root' => '/var/www/storage',
    ];

    $env = $provider->buildEnvironment('local_disk', $config);

    expect($env)
        ->toHaveKey('RCLONE_CONFIG_LOCAL_DISK_TYPE', 'local')
        ->toHaveKey('RCLONE_CONFIG_LOCAL_DISK_ROOT', '/var/www/storage');
});

it('uses default root path when not specified', function () {
    $provider = new LocalProvider;
    $config = [
        'driver' => 'local',
    ];

    $env = $provider->buildEnvironment('local_disk', $config);

    expect($env)
        ->toHaveKey('RCLONE_CONFIG_LOCAL_DISK_TYPE', 'local')
        ->toHaveKey('RCLONE_CONFIG_LOCAL_DISK_ROOT', '/');
});

it('throws exception for wrong driver', function () {
    $provider = new LocalProvider;
    $config = [
        'driver' => 's3',
    ];

    expect(fn () => $provider->buildEnvironment('local_disk', $config))
        ->toThrow(InvalidArgumentException::class, "Driver mismatch: expected 'local', got 's3'");
});
