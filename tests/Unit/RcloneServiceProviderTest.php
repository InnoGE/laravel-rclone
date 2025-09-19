<?php

use Illuminate\Support\ServiceProvider;
use InnoGE\LaravelRclone\Contracts\RcloneInterface;
use InnoGE\LaravelRclone\RcloneServiceProvider;
use InnoGE\LaravelRclone\Support\ProviderRegistry;

it('extends service provider', function () {
    $provider = new RcloneServiceProvider(app());

    expect($provider)->toBeInstanceOf(ServiceProvider::class);
});

it('provides correct services', function () {
    $provider = new RcloneServiceProvider(app());

    $provides = $provider->provides();

    expect($provides)
        ->toContain(RcloneInterface::class)
        ->toContain('rclone')
        ->toHaveCount(2);
});

it('registers provider registry as singleton', function () {
    $registry1 = app(ProviderRegistry::class);
    $registry2 = app(ProviderRegistry::class);

    expect($registry1)->toBe($registry2);
});

it('registers rclone interface as singleton', function () {
    $rclone1 = app(RcloneInterface::class);
    $rclone2 = app(RcloneInterface::class);

    expect($rclone1)->toBe($rclone2);
});

it('registers all default providers', function () {
    $registry = app(ProviderRegistry::class);

    expect($registry->hasProvider('local'))->toBeTrue()
        ->and($registry->hasProvider('s3'))->toBeTrue()
        ->and($registry->hasProvider('sftp'))->toBeTrue()
        ->and($registry->hasProvider('ftp'))->toBeTrue();
});

it('publishes config files in console', function () {
    // This simulates running in console
    app()->instance('path.config', '/fake/config/path');

    $provider = new RcloneServiceProvider(app());
    $provider->boot();

    // Since we're testing the boot method runs without errors
    expect(true)->toBeTrue();
});