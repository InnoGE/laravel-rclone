<?php

use InnoGE\LaravelRclone\Exceptions\ProviderNotFoundException;

test('can create for driver exception', function () {
    $exception = ProviderNotFoundException::forDriver('unknown');

    expect($exception)->toBeInstanceOf(ProviderNotFoundException::class);
    expect($exception->getMessage())->toBe('No provider found for driver: unknown');
});

test('can create for driver exception with different drivers', function () {
    $exception1 = ProviderNotFoundException::forDriver('custom-driver');
    $exception2 = ProviderNotFoundException::forDriver('missing-provider');

    expect($exception1->getMessage())->toBe('No provider found for driver: custom-driver');
    expect($exception2->getMessage())->toBe('No provider found for driver: missing-provider');
});
