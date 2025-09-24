<?php

use InnoGE\LaravelRclone\Support\ProcessResult;

test('all getter methods return correct values', function () {
    $stats = [
        'transferred_files' => 100,
        'transferred_bytes' => 2048,
        'errors' => 5,
        'custom_stat' => 'value',
    ];

    $result = new ProcessResult(
        successful: true,
        exitCode: 0,
        output: 'Transfer complete',
        errorOutput: 'INFO: Done',
        stats: $stats
    );

    // Test all public methods
    expect($result->isSuccessful())->toBeTrue();
    expect($result->failed())->toBeFalse();
    expect($result->getExitCode())->toBe(0);
    expect($result->getOutput())->toBe('Transfer complete');
    expect($result->getErrorOutput())->toBe('INFO: Done');
    expect($result->getStats())->toBe($stats);

    // Test individual stat getters
    expect($result->getTransferredFiles())->toBe(100);
    expect($result->getTransferredBytes())->toBe(2048);
    expect($result->getErrors())->toBe(5);

    // Test getStatValue method
    expect($result->getStatValue('transferred_files'))->toBe(100);
    expect($result->getStatValue('custom_stat'))->toBe('value');
    expect($result->getStatValue('missing'))->toBe(0);
    expect($result->getStatValue('missing', 'default'))->toBe('default');
});

test('handles failed process correctly', function () {
    $result = new ProcessResult(
        successful: false,
        exitCode: 1,
        output: '',
        errorOutput: 'Error occurred',
        stats: []
    );

    expect($result->isSuccessful())->toBeFalse();
    expect($result->failed())->toBeTrue();
    expect($result->getExitCode())->toBe(1);
});

test('handles empty stats correctly', function () {
    $result = new ProcessResult(
        successful: true,
        exitCode: 0,
        output: 'Done',
        errorOutput: '',
        stats: []
    );

    expect($result->getTransferredFiles())->toBe(0);
    expect($result->getTransferredBytes())->toBe(0);
    expect($result->getErrors())->toBe(0);
    expect($result->getStatValue('anything'))->toBe(0);
});

test('readonly properties are accessible', function () {
    $stats = ['test' => 'value'];
    $result = new ProcessResult(
        successful: true,
        exitCode: 123,
        output: 'test output',
        errorOutput: 'test error',
        stats: $stats
    );

    expect($result->successful)->toBeTrue();
    expect($result->exitCode)->toBe(123);
    expect($result->output)->toBe('test output');
    expect($result->errorOutput)->toBe('test error');
    expect($result->stats)->toBe($stats);
});
