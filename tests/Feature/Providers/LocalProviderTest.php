<?php

use InnoGE\LaravelRclone\Providers\LocalProvider;

test('builds remote path with default root', function () {
    $provider = new LocalProvider;

    $config = [
        'driver' => 'local',
    ];

    $path = $provider->buildRemotePath('local_test', 'backups', $config);

    expect($path)->toBe('/backups');
});

test('builds remote path with custom root', function () {
    $provider = new LocalProvider;

    $config = [
        'driver' => 'local',
        'root' => '/storage/app',
    ];

    $path = $provider->buildRemotePath('local_test', 'backups', $config);

    expect($path)->toBe('/storage/app/backups');
});

test('builds remote path with root and trailing slashes', function () {
    $provider = new LocalProvider;

    $config = [
        'driver' => 'local',
        'root' => '/storage/app/',
    ];

    $path = $provider->buildRemotePath('local_test', '/backups/', $config);

    expect($path)->toBe('/storage/app/backups/');
});

test('builds remote path with empty path', function () {
    $provider = new LocalProvider;

    $config = [
        'driver' => 'local',
        'root' => '/storage/app',
    ];

    $path = $provider->buildRemotePath('local_test', '/', $config);

    expect($path)->toBe('/storage/app/');
});

test('builds remote path without root', function () {
    $provider = new LocalProvider;

    $config = [
        'driver' => 'local',
    ];

    $path = $provider->buildRemotePath('local_test', '/', $config);

    expect($path)->toBe('/');
});

test('builds remote path with nested directories', function () {
    $provider = new LocalProvider;

    $config = [
        'driver' => 'local',
        'root' => '/var/www',
    ];

    $path = $provider->buildRemotePath('local_test', 'uploads/images/2024', $config);

    expect($path)->toBe('/var/www/uploads/images/2024');
});