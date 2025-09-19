<?php

use InnoGE\LaravelRclone\Support\ProcessResult;

it('can be created with all properties', function () {
    $result = new ProcessResult(
        successful: true,
        exitCode: 0,
        output: 'Success output',
        errorOutput: 'Error output',
        stats: ['transferred_files' => 5, 'transferred_bytes' => 1024, 'errors' => 2]
    );

    expect($result->successful)->toBeTrue()
        ->and($result->exitCode)->toBe(0)
        ->and($result->output)->toBe('Success output')
        ->and($result->errorOutput)->toBe('Error output')
        ->and($result->stats)->toBe(['transferred_files' => 5, 'transferred_bytes' => 1024, 'errors' => 2]);
});

it('returns correct success status', function () {
    $successResult = new ProcessResult(true, 0, '', '', []);
    $failResult = new ProcessResult(false, 1, '', '', []);

    expect($successResult->isSuccessful())->toBeTrue()
        ->and($failResult->isSuccessful())->toBeFalse();
});

it('returns correct failure status', function () {
    $successResult = new ProcessResult(true, 0, '', '', []);
    $failResult = new ProcessResult(false, 1, '', '', []);

    expect($successResult->failed())->toBeFalse()
        ->and($failResult->failed())->toBeTrue();
});

it('returns correct output', function () {
    $result = new ProcessResult(true, 0, 'test output', 'test error', []);

    expect($result->getOutput())->toBe('test output')
        ->and($result->getErrorOutput())->toBe('test error');
});

it('returns correct exit code', function () {
    $result = new ProcessResult(false, 127, '', '', []);

    expect($result->getExitCode())->toBe(127);
});

it('returns complete stats array', function () {
    $stats = ['transferred_files' => 10, 'transferred_bytes' => 2048, 'errors' => 1];
    $result = new ProcessResult(true, 0, '', '', $stats);

    expect($result->getStats())->toBe($stats);
});

it('returns individual stat values', function () {
    $result = new ProcessResult(
        true, 0, '', '',
        ['transferred_files' => 15, 'transferred_bytes' => 4096, 'errors' => 3]
    );

    expect($result->getTransferredFiles())->toBe(15)
        ->and($result->getTransferredBytes())->toBe(4096)
        ->and($result->getErrors())->toBe(3);
});

it('returns default values for missing stats', function () {
    $result = new ProcessResult(true, 0, '', '', []);

    expect($result->getTransferredFiles())->toBe(0)
        ->and($result->getTransferredBytes())->toBe(0)
        ->and($result->getErrors())->toBe(0);
});