<?php

use InnoGE\LaravelRclone\Exceptions\CommandExecutionException;

test('can create binary not found exception', function () {
    $exception = CommandExecutionException::binaryNotFound();

    expect($exception)->toBeInstanceOf(CommandExecutionException::class);
    expect($exception->getMessage())->toBe(
        'rclone binary not found. Please install rclone or set binary_path in configuration.'
    );
});

test('can create invalid parameters exception', function () {
    $exception = CommandExecutionException::invalidParameters('transfers', 'invalid_value');

    expect($exception)->toBeInstanceOf(CommandExecutionException::class);
    expect($exception->getMessage())->toBe("Invalid parameter 'transfers': invalid_value");
});

test('can create missing disk exception', function () {
    $exception = CommandExecutionException::missingDisk('Source');

    expect($exception)->toBeInstanceOf(CommandExecutionException::class);
    expect($exception->getMessage())->toBe('Source disk is required');
});

test('can create missing disk exception for target', function () {
    $exception = CommandExecutionException::missingDisk('Target');

    expect($exception)->toBeInstanceOf(CommandExecutionException::class);
    expect($exception->getMessage())->toBe('Target disk is required');
});
