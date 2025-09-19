<?php

use Illuminate\Support\Facades\Process;
use InnoGE\LaravelRclone\Facades\Rclone;

describe('Stats Parsing', function () {
    test('parses basic rclone statistics correctly', function () {
        Process::fake([
            '*' => Process::result(
                output: "Transfer complete",
                errorOutput: "Transferred: 150 / 200, 75%\nTransferred: 1048576 Bytes, 1 MiB/s, ETA -\nErrors: 2\nChecks: 45 in 30s\nElapsed time: 30.5s",
                exitCode: 0
            ),
        ]);

        $result = Rclone::source('local_test', '/source')
            ->target('s3_test', '/target')
            ->sync();

        $stats = $result->getStats();

        expect($stats['transferred_files'])->toBe(150);
        expect($stats['total_files'])->toBe(200);
        expect($stats['transferred_bytes'])->toBe(1048576);
        expect($stats['errors'])->toBe(2);
        expect($stats['checks'])->toBe(45);
        expect($stats['elapsed_time'])->toBe(30.5);
        expect($stats['success_rate'])->toBeGreaterThan(70);
    });

    test('parses complex rclone output with units', function () {
        Process::fake([
            '*' => Process::result(
                output: "Sync complete",
                errorOutput: "Transferred: 850 / 1000, 85%\nTransferred: 10.5 GiB / 12.3 GiB, 85%, 150 MiB/s, ETA 12s\nErrors: 5\nChecks: 142 in 5m30s\nDeleted: 12\nRenamed: 3\nElapsed time: 330.2s",
                exitCode: 0
            ),
        ]);

        $result = Rclone::source('local_test', '/large_dataset')
            ->target('s3_test', '/backup')
            ->sync();

        $stats = $result->getStats();

        expect($stats['transferred_files'])->toBe(850);
        expect($stats['total_files'])->toBe(1000);
        expect($stats['errors'])->toBe(5);
        expect($stats['checks'])->toBe(142);
        expect($stats['deletes'])->toBe(12);
        expect($stats['renames'])->toBe(3);
        expect($stats['elapsed_time'])->toBe(330.2);

        // Calculated metrics
        expect($stats['success_rate'])->toBe(99.5); // (1000-5)/1000 * 100
        expect($stats['transfer_speed_mbps'])->toBeGreaterThan(0);
    });

    test('handles output without statistics gracefully', function () {
        Process::fake([
            '*' => Process::result(
                output: "Simple operation complete",
                errorOutput: "No detailed stats available",
                exitCode: 0
            ),
        ]);

        $result = Rclone::source('local_test', '/source')
            ->target('s3_test', '/target')
            ->sync();

        $stats = $result->getStats();

        // Should return default values when no stats found
        expect($stats['transferred_files'])->toBe(0);
        expect($stats['transferred_bytes'])->toBe(0);
        expect($stats['errors'])->toBe(0);
        expect($stats['success_rate'])->toBe(100.0);
        expect($stats['transfer_speed_mbps'])->toBe(0.0);
    });

    test('calculates transfer speed correctly', function () {
        Process::fake([
            '*' => Process::result(
                output: "Transfer complete",
                errorOutput: "Transferred: 100 / 100, 100%\nTransferred: 104857600 Bytes, 10 MiB/s, ETA -\nElapsed time: 10.0s",
                exitCode: 0
            ),
        ]);

        $result = Rclone::source('local_test', '/source')
            ->target('s3_test', '/target')
            ->sync();

        $stats = $result->getStats();

        // Should calculate some transfer speed
        expect($stats['transfer_speed_mbps'])->toBeGreaterThan(70);
    });

    test('parses percentage and ETA correctly', function () {
        Process::fake([
            '*' => Process::result(
                output: "Transfer in progress",
                errorOutput: "Transferred: 300 / 500, 60%\nETA 2:30\nElapsed time: 45.0s",
                exitCode: 0
            ),
        ]);

        $result = Rclone::source('local_test', '/source')
            ->target('s3_test', '/target')
            ->sync();

        $stats = $result->getStats();

        expect($stats['percentage'])->toBe(60);
        expect($stats['eta'])->toBe(150); // 2:30 = 150 seconds
    });

    test('handles zero elapsed time without division by zero', function () {
        Process::fake([
            '*' => Process::result(
                output: "Quick transfer",
                errorOutput: "Transferred: 1 / 1, 100%\nTransferred: 1024 Bytes\nElapsed time: 0.0s",
                exitCode: 0
            ),
        ]);

        $result = Rclone::source('local_test', '/source')
            ->target('s3_test', '/target')
            ->sync();

        $stats = $result->getStats();

        // Should not cause division by zero error
        expect($stats['transfer_speed_mbps'])->toBe(0.0);
        expect($stats['success_rate'])->toBe(100.0);
    });
});

describe('ProcessResult', function () {
    test('provides comprehensive result information', function () {
        Process::fake([
            '*' => Process::result(
                output: "Operation successful",
                errorOutput: "INFO: Transfer completed\nTransferred: 50 / 50, 100%\nErrors: 0",
                exitCode: 0
            ),
        ]);

        $result = Rclone::source('local_test', '/source')
            ->target('s3_test', '/target')
            ->sync();

        expect($result->isSuccessful())->toBeTrue();
        expect($result->failed())->toBeFalse();
        expect($result->getExitCode())->toBe(0);
        expect($result->getOutput())->toContain('Operation successful');
        expect($result->getErrorOutput())->toContain('Transfer completed');

        $stats = $result->getStats();
        expect($stats)->toBeArray();
        expect($stats['transferred_files'])->toBe(50);
        expect($stats['errors'])->toBe(0);
    });

    test('handles failed operations correctly', function () {
        Process::fake([
            '*' => Process::result(
                output: "",
                errorOutput: "ERROR: Access denied\nFailed to connect to remote",
                exitCode: 1
            ),
        ]);

        $result = Rclone::source('local_test', '/source')
            ->target('s3_test', '/target')
            ->sync();

        expect($result->failed())->toBeTrue();
        expect($result->isSuccessful())->toBeFalse();
        expect($result->getExitCode())->toBe(1);
        expect($result->getErrorOutput())->toContain('Access denied');

        // Stats should still be parsed even for failed operations
        $stats = $result->getStats();
        expect($stats)->toBeArray();
    });

    test('individual stat accessors work correctly', function () {
        Process::fake([
            '*' => Process::result(
                output: "Transfer complete",
                errorOutput: "Transferred: 25 / 30, 83%\nTransferred: 2048 Bytes\nErrors: 1\nChecks: 15\nElapsed time: 20.0s",
                exitCode: 0
            ),
        ]);

        $result = Rclone::source('local_test', '/source')
            ->target('s3_test', '/target')
            ->sync();

        expect($result->getStatValue('transferred_files'))->toBe(25);
        expect($result->getStatValue('total_files'))->toBe(30);
        expect($result->getStatValue('transferred_bytes'))->toBe(2048);
        expect($result->getStatValue('errors'))->toBe(1);
        expect($result->getStatValue('checks'))->toBe(15);
        expect($result->getStatValue('elapsed_time'))->toBe(20.0);

        // Non-existent stat should return default
        expect($result->getStatValue('non_existent', 999))->toBe(999);
    });
});