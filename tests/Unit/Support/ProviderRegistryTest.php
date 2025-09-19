<?php

use InnoGE\LaravelRclone\Providers\LocalProvider;
use InnoGE\LaravelRclone\Providers\S3Provider;
use InnoGE\LaravelRclone\Support\ProviderRegistry;

it('can register and retrieve providers', function () {
    $registry = new ProviderRegistry;
    $provider = new LocalProvider;

    $registry->register($provider);

    expect($registry->getProvider('local'))->toBe($provider);
});

it('throws exception for unknown provider', function () {
    $registry = new ProviderRegistry;

    expect(fn () => $registry->getProvider('unknown'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported driver: unknown');
});

it('can check if provider exists', function () {
    $registry = new ProviderRegistry;
    $registry->register(new LocalProvider);

    expect($registry->hasProvider('local'))->toBeTrue();
    expect($registry->hasProvider('s3'))->toBeFalse();
});

it('returns all registered providers', function () {
    $registry = new ProviderRegistry;
    $localProvider = new LocalProvider;
    $s3Provider = new S3Provider;

    $registry->register($localProvider);
    $registry->register($s3Provider);

    $providers = $registry->getProviders();

    expect($providers)
        ->toHaveCount(2)
        ->toHaveKey('local', $localProvider)
        ->toHaveKey('s3', $s3Provider);
});

it('returns supported driver names', function () {
    $registry = new ProviderRegistry;
    $registry->register(new LocalProvider);
    $registry->register(new S3Provider);

    $drivers = $registry->getSupportedDrivers();

    expect($drivers)
        ->toContain('local')
        ->toContain('s3')
        ->toHaveCount(2);
});
