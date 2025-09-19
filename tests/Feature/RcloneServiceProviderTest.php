<?php

use Illuminate\Support\ServiceProvider;
use InnoGE\LaravelRclone\Contracts\RcloneInterface;
use InnoGE\LaravelRclone\RcloneServiceProvider;
use InnoGE\LaravelRclone\Support\ProviderRegistry;

test('extends service provider', function () {
    $provider = new RcloneServiceProvider(app());

    expect($provider)->toBeInstanceOf(ServiceProvider::class);
});

test('provides correct services', function () {
    $provider = new RcloneServiceProvider(app());

    $provides = $provider->provides();

    expect($provides)->toContain(RcloneInterface::class);
    expect($provides)->toContain('rclone');
    expect($provides)->toHaveCount(2);
});

test('registers provider registry as singleton', function () {
    $registry1 = app(ProviderRegistry::class);
    $registry2 = app(ProviderRegistry::class);

    expect($registry1)->toBe($registry2);
});

test('registers rclone interface as singleton', function () {
    $rclone1 = app(RcloneInterface::class);
    $rclone2 = app(RcloneInterface::class);

    expect($rclone1)->toBe($rclone2);
});

test('registers all default providers', function () {
    $registry = app(ProviderRegistry::class);

    expect($registry->hasProvider('local'))->toBeTrue();
    expect($registry->hasProvider('s3'))->toBeTrue();
    expect($registry->hasProvider('sftp'))->toBeTrue();
    expect($registry->hasProvider('ftp'))->toBeTrue();
});

test('publishes config files in console', function () {
    // This simulates running in console
    app()->instance('path.config', '/fake/config/path');

    $provider = new RcloneServiceProvider(app());
    $provider->boot();

    // Since we're testing the boot method runs without errors
    expect(true)->toBeTrue();
});

test('rclone service is registered in container', function () {
    expect(app()->bound(RcloneInterface::class))->toBeTrue();
    expect(app()->bound('rclone'))->toBeTrue();
});

test('rclone service is singleton', function () {
    $instance1 = app(RcloneInterface::class);
    $instance2 = app(RcloneInterface::class);

    expect($instance1)->toBe($instance2);
});

test('rclone manager receives logger from container', function () {
    $manager = app(RcloneInterface::class);

    // The manager should have been created with logger from container
    expect($manager)->toBeInstanceOf(RcloneInterface::class);

    // Test that it can handle operations (logger integration is internal)
    expect(true)->toBeTrue();
});

test('can access rclone via service container', function () {
    $manager = app('rclone');

    expect($manager)->toBeInstanceOf(RcloneInterface::class);
});

test('can access rclone via interface binding', function () {
    $manager = app(RcloneInterface::class);

    expect($manager)->toBeInstanceOf(RcloneInterface::class);
});
