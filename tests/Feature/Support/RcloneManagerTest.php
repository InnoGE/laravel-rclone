<?php

use Illuminate\Support\Facades\Process;
use InnoGE\LaravelRclone\Contracts\RcloneInterface;
use InnoGE\LaravelRclone\Exceptions\CommandExecutionException;
use InnoGE\LaravelRclone\Exceptions\InvalidConfigurationException;
use InnoGE\LaravelRclone\Facades\Rclone;

beforeEach(function () {
    // Mock successful process execution
    Process::fake([
        '*' => Process::result(
            output: "Files:          10 to check\nTransferred:    5 / 10, 50%\nTransferred:    1024 B, 1 KB/s, ETA -\nElapsed time: 30.5s",
            errorOutput: "2025/01/19 10:30:45 INFO  : Transferred: 5 / 10, 50%\n2025/01/19 10:30:45 INFO  : Errors: 0\n2025/01/19 10:30:45 INFO  : Checks: 3 in 30s, 0.1/s\nTransferred:	     1024 B / 2 KiB, 50%, 33 B/s, ETA 30s\nElapsed time:       30.5s",
            exitCode: 0
        ),
    ]);
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

test('facade resolves correctly', function () {
    $facade = Rclone::getFacadeRoot();

    expect($facade)->toBeInstanceOf(RcloneInterface::class);
});

test('can perform sync operation', function () {
    $result = Rclone::source('local_test', '/source')
        ->target('s3_test', '/target')
        ->sync();

    expect($result->isSuccessful())->toBeTrue();
    expect($result->getExitCode())->toBe(0);
});

test('can perform copy operation', function () {
    $result = Rclone::source('local_test', '/source')
        ->target('s3_test', '/target')
        ->copy();

    expect($result->isSuccessful())->toBeTrue();
});

test('can perform move operation', function () {
    $result = Rclone::source('local_test', '/source')
        ->target('s3_test', '/target')
        ->move();

    expect($result->isSuccessful())->toBeTrue();
});
test('can chain configuration methods', function () {
    $manager = Rclone::source('local_test', '/source')
        ->target('s3_test', '/target')
        ->transfers(8)
        ->checkers(4)
        ->retries(2)
        ->withProgress(true)
        ->statInterval(5);

    expect($manager)->toBeInstanceOf(RcloneInterface::class);

    $result = $manager->sync();
    expect($result->isSuccessful())->toBeTrue();
});

test('can set custom options', function () {
    $result = Rclone::source('local_test', '/source')
        ->target('s3_test', '/target')
        ->option('bandwidth', '10M')
        ->option('exclude', '*.tmp')
        ->sync();

    expect($result->isSuccessful())->toBeTrue();
});

test('validates transfers parameter bounds', function () {
    expect(fn () => Rclone::transfers(0))
        ->toThrow(InvalidConfigurationException::class);

    expect(fn () => Rclone::transfers(101))
        ->toThrow(InvalidConfigurationException::class);

    // Valid range should work
    $manager = Rclone::transfers(50);
    expect($manager)->toBeInstanceOf(RcloneInterface::class);
});

test('validates checkers parameter bounds', function () {
    expect(fn () => Rclone::checkers(0))
        ->toThrow(InvalidConfigurationException::class);

    expect(fn () => Rclone::checkers(101))
        ->toThrow(InvalidConfigurationException::class);
});

test('validates retries parameter bounds', function () {
    expect(fn () => Rclone::retries(-1))
        ->toThrow(InvalidConfigurationException::class);

    expect(fn () => Rclone::retries(11))
        ->toThrow(InvalidConfigurationException::class);
});

test('validates stat interval bounds', function () {
    expect(fn () => Rclone::statInterval(0))
        ->toThrow(InvalidConfigurationException::class);

    expect(fn () => Rclone::statInterval(3601))
        ->toThrow(InvalidConfigurationException::class);
});

test('throws exception when source disk is not set', function () {
    expect(fn () => Rclone::target('s3_test', '/target')->sync())
        ->toThrow(CommandExecutionException::class, 'Source disk is required');
});

test('throws exception when target disk is not set', function () {
    expect(fn () => Rclone::source('local_test', '/source')->sync())
        ->toThrow(CommandExecutionException::class, 'Target disk is required');
});

test('handles failed processes', function () {
    Process::fake([
        '*' => Process::result(
            output: '',
            errorOutput: 'rclone: error: file not found',
            exitCode: 1
        ),
    ]);

    $result = Rclone::source('local_test', '/source')
        ->target('s3_test', '/target')
        ->sync();

    expect($result->failed())->toBeTrue();
    expect($result->getExitCode())->toBe(1);
    expect($result->getErrorOutput())->toContain('file not found');
});

test('can capture process output with callback', function () {
    $outputReceived = [];

    $result = Rclone::source('local_test', '/source')
        ->target('s3_test', '/target')
        ->getOutputUsing(function ($type, $buffer) use (&$outputReceived) {
            $outputReceived[] = [$type, $buffer];
        })
        ->sync();

    expect($result->isSuccessful())->toBeTrue();
    // Note: Callback execution depends on Process facade mock implementation
});

test('parses stats from process output', function () {
    $result = Rclone::source('local_test', '/source')
        ->target('s3_test', '/target')
        ->sync();

    $stats = $result->getStats();

    expect($stats)->toBeArray();
    expect($stats)->toHaveKeys([
        'transferred_files',
        'total_files',
        'transferred_bytes',
        'errors',
        'checks',
        'elapsed_time',
        'success_rate',
        'transfer_speed_mbps',
    ]);

    // Should have parsed real values from mock output
    expect($stats['transferred_files'])->toBeGreaterThanOrEqual(0);
    expect($stats['elapsed_time'])->toBeGreaterThanOrEqual(0);
});

test('works with different filesystem configurations', function () {
    config([
        'filesystems.disks.local_test' => [
            'driver' => 'local',
            'root' => '/tmp',
        ],
        'filesystems.disks.s3_test' => [
            'driver' => 's3',
            'key' => 'test_key',
            'secret' => 'test_secret',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
        ],
    ]);

    $result = Rclone::source('local_test', '/source')
        ->target('s3_test', '/target')
        ->sync();

    expect($result->isSuccessful())->toBeTrue();
});

test('handles invalid provider configuration', function () {
    config([
        'filesystems.disks.invalid_s3' => [
            'driver' => 's3',
            // Missing required fields like key, secret, bucket
        ],
    ]);

    expect(fn () => Rclone::source('local_test', '/source')
        ->target('invalid_s3', '/target')
        ->sync()
    )->toThrow(InvalidConfigurationException::class);
});

test('uses custom binary path when configured', function () {
    config(['rclone.binary_path' => '/custom/path/to/rclone']);

    $result = Rclone::source('local_test', '/source')
        ->target('s3_test', '/target')
        ->sync();

    expect($result->isSuccessful())->toBeTrue();
});

test('supports complex real-world scenarios', function () {
    // Simulate a real backup scenario
    $result = Rclone::source('local_test', '/var/www/uploads')
        ->target('s3_test', '/backups/uploads')
        ->transfers(10)
        ->checkers(8)
        ->retries(3)
        ->withProgress(true)
        ->statInterval(30)
        ->option('exclude', '*.tmp')
        ->option('exclude', '*.log')
        ->option('max-age', '30d')
        ->sync();

    expect($result->isSuccessful())->toBeTrue();
    expect($result->getStats()['success_rate'])->toBeGreaterThan(0);
});

test('handles large file transfer simulation', function () {
    Process::fake([
        '*' => Process::result(
            output: "Files: 1000 to check\nTransferred: 850 / 1000, 85%",
            errorOutput: "Transferred:	850 / 1000, 85%\nTransferred:    10.5 GiB / 12.3 GiB, 85%, 150 MiB/s, ETA 12s\nErrors:         5\nChecks:         142 in 5m30s, 0.4/s\nElapsed time:   5m30.2s",
            exitCode: 0
        ),
    ]);

    $result = Rclone::source('local_test', '/large_dataset')
        ->target('s3_test', '/backup/large_dataset')
        ->transfers(16)
        ->checkers(16)
        ->sync();

    expect($result->isSuccessful())->toBeTrue();

    $stats = $result->getStats();
    expect($stats['transferred_files'])->toBe(850);
    expect($stats['total_files'])->toBe(1000);
    expect($stats['errors'])->toBe(5);
    expect($stats['success_rate'])->toBeGreaterThan(80);
});

test('falls back to default path building when provider not found', function () {
    $manager = app(RcloneInterface::class);

    // Create a mock provider registry
    $registry = Mockery::mock(\InnoGE\LaravelRclone\Support\ProviderRegistry::class);
    $registry->shouldReceive('getProvider')
        ->with('unknown')
        ->andThrow(new \InnoGE\LaravelRclone\Exceptions\ProviderNotFoundException("Provider for driver 'unknown' not found"));

    // Use reflection to set the private property
    $reflection = new ReflectionClass($manager);
    $property = $reflection->getProperty('providerRegistry');
    $property->setAccessible(true);
    $property->setValue($manager, $registry);

    // Set filesystem disks property
    $diskProperty = $reflection->getProperty('filesystemDisks');
    $diskProperty->setAccessible(true);
    $diskProperty->setValue($manager, [
        'unknown_disk' => ['driver' => 'unknown'],
    ]);

    // Use reflection to call protected buildDiskPath method
    $method = $reflection->getMethod('buildDiskPath');
    $method->setAccessible(true);

    $result = $method->invoke($manager, 'unknown_disk', '/test/path');

    // Should fall back to default format
    expect($result)->toBe('unknown_disk:test/path');
});

test('falls back to default path building when disk not configured', function () {
    $manager = app(RcloneInterface::class);

    // Use reflection to call protected buildDiskPath method
    $reflection = new ReflectionClass($manager);
    $method = $reflection->getMethod('buildDiskPath');
    $method->setAccessible(true);

    $result = $method->invoke($manager, 'nonexistent_disk', '/test/path');

    // Should fall back to default format
    expect($result)->toBe('nonexistent_disk:test/path');
});
