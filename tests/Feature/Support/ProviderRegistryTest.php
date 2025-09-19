<?php

use InnoGE\LaravelRclone\Exceptions\ProviderNotFoundException;
use InnoGE\LaravelRclone\Providers\LocalProvider;
use InnoGE\LaravelRclone\Providers\S3Provider;
use InnoGE\LaravelRclone\Support\ProviderRegistry;

describe('ProviderRegistry Complete Coverage', function () {
    test('can register and retrieve providers', function () {
        $registry = new ProviderRegistry();
        $localProvider = new LocalProvider();
        $s3Provider = new S3Provider();

        $registry->register($localProvider);
        $registry->register($s3Provider);

        expect($registry->getProvider('local'))->toBe($localProvider);
        expect($registry->getProvider('s3'))->toBe($s3Provider);
    });

    test('throws exception for unknown provider', function () {
        $registry = new ProviderRegistry();

        expect(fn () => $registry->getProvider('unknown'))
            ->toThrow(ProviderNotFoundException::class, 'No provider found for driver: unknown');
    });

    test('can check if provider exists', function () {
        $registry = new ProviderRegistry();
        $registry->register(new LocalProvider());

        expect($registry->hasProvider('local'))->toBeTrue();
        expect($registry->hasProvider('nonexistent'))->toBeFalse();
    });

    test('returns all registered providers', function () {
        $registry = new ProviderRegistry();
        $localProvider = new LocalProvider();
        $s3Provider = new S3Provider();

        $registry->register($localProvider);
        $registry->register($s3Provider);

        $providers = $registry->getProviders();

        expect($providers)->toHaveCount(2);
        expect($providers['local'])->toBe($localProvider);
        expect($providers['s3'])->toBe($s3Provider);
    });

    test('returns supported driver names', function () {
        $registry = new ProviderRegistry();
        $registry->register(new LocalProvider());
        $registry->register(new S3Provider());

        $drivers = $registry->getSupportedDrivers();

        expect($drivers)->toEqual(['local', 's3']);
        expect($drivers)->toHaveCount(2);
    });

    test('can override existing provider', function () {
        $registry = new ProviderRegistry();
        $localProvider1 = new LocalProvider();
        $localProvider2 = new LocalProvider();

        $registry->register($localProvider1);
        expect($registry->getProvider('local'))->toBe($localProvider1);

        // Register another provider with same driver - should override
        $registry->register($localProvider2);
        expect($registry->getProvider('local'))->toBe($localProvider2);
        expect($registry->getProviders())->toHaveCount(1);
    });

    test('empty registry works correctly', function () {
        $registry = new ProviderRegistry();

        expect($registry->getProviders())->toBeEmpty();
        expect($registry->getSupportedDrivers())->toBeEmpty();
        expect($registry->hasProvider('any'))->toBeFalse();
    });
});