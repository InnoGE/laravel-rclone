<?php

use InnoGE\LaravelRclone\Exceptions\InvalidConfigurationException;
use InnoGE\LaravelRclone\Support\RcloneCommandBuilder;

test('can create builder with create static method', function () {
    $builder = RcloneCommandBuilder::create('/usr/bin/rclone', 'sync');

    expect($builder)->toBeInstanceOf(RcloneCommandBuilder::class);

    $command = $builder->build();
    expect($command)->toBe(['/usr/bin/rclone', 'sync']);
});

test('can add base options', function () {
    $builder = RcloneCommandBuilder::create('/usr/bin/rclone', 'copy');
    $builder->addBaseOptions(['--verbose', '--checksum']);

    $command = $builder->build();
    expect($command)->toBe(['/usr/bin/rclone', 'copy', '--verbose', '--checksum']);
});

test('can add progress option', function () {
    $builder = RcloneCommandBuilder::create('/usr/bin/rclone', 'sync');

    // Test enabled progress
    $builder->addProgressOption(true);
    $command = $builder->build();
    expect($command)->toBe(['/usr/bin/rclone', 'sync', '--progress']);

    // Test disabled progress (should not add option)
    $builder2 = RcloneCommandBuilder::create('/usr/bin/rclone', 'sync');
    $builder2->addProgressOption(false);
    $command2 = $builder2->build();
    expect($command2)->toBe(['/usr/bin/rclone', 'sync']);
});

test('validates option values correctly', function () {
    $builder = RcloneCommandBuilder::create('/usr/bin/rclone', 'sync');

    // Test valid values
    $builder->addOption('transfers', 8);
    $builder->addOption('checkers', 4);
    $builder->addOption('retries', 3);
    $builder->addOption('stat_interval', 60);

    $command = $builder->build();
    expect($command)->toContain('--transfers=8');
    expect($command)->toContain('--checkers=4');
    expect($command)->toContain('--retries=3');
    expect($command)->toContain('--stats=60s');

    expect(true)->toBeTrue(); // Should not throw
});

test('throws exception for invalid transfers value', function () {
    $builder = RcloneCommandBuilder::create('/usr/bin/rclone', 'sync');

    // Test boundary values
    expect(fn () => $builder->addOption('transfers', 0))
        ->toThrow(InvalidConfigurationException::class, 'integer >= 1');

    expect(fn () => $builder->addOption('transfers', 101))
        ->toThrow(InvalidConfigurationException::class, 'integer <= 100');
});

test('throws exception for invalid checkers value', function () {
    $builder = RcloneCommandBuilder::create('/usr/bin/rclone', 'sync');

    expect(fn () => $builder->addOption('checkers', 0))
        ->toThrow(InvalidConfigurationException::class, 'integer >= 1');

    expect(fn () => $builder->addOption('checkers', 101))
        ->toThrow(InvalidConfigurationException::class, 'integer <= 100');
});

test('throws exception for invalid retries value', function () {
    $builder = RcloneCommandBuilder::create('/usr/bin/rclone', 'sync');

    expect(fn () => $builder->addOption('retries', -1))
        ->toThrow(InvalidConfigurationException::class, 'integer >= 0');

    expect(fn () => $builder->addOption('retries', 11))
        ->toThrow(InvalidConfigurationException::class, 'integer <= 10');
});

test('throws exception for invalid stat_interval value', function () {
    $builder = RcloneCommandBuilder::create('/usr/bin/rclone', 'sync');

    expect(fn () => $builder->addOption('stat_interval', 0))
        ->toThrow(InvalidConfigurationException::class, 'integer >= 1');

    expect(fn () => $builder->addOption('stat_interval', 3601))
        ->toThrow(InvalidConfigurationException::class, 'integer <= 3600');
});

test('throws exception for non-integer values', function () {
    $builder = RcloneCommandBuilder::create('/usr/bin/rclone', 'sync');

    expect(fn () => $builder->addOption('transfers', 'not-an-integer'))
        ->toThrow(InvalidConfigurationException::class, 'integer');
});

test('handles custom options correctly', function () {
    $builder = RcloneCommandBuilder::create('/usr/bin/rclone', 'sync');

    // Custom options should be added to the command
    $builder->addOption('custom_option', 'custom_value');
    $builder->addOption('dry_run', true);
    $builder->addOption('disabled_flag', false);

    $command = $builder->build();

    expect($command)->toContain('--custom_option=custom_value');
    expect($command)->toContain('--dry_run');
    expect($command)->not->toContain('--disabled_flag');

    expect(true)->toBeTrue(); // Should not throw
});

test('can add source and target', function () {
    $builder = RcloneCommandBuilder::create('/usr/bin/rclone', 'sync');
    $builder->addSourceAndTarget('local:source', 's3:target');

    $command = $builder->build();
    expect($command)->toBe(['/usr/bin/rclone', 'sync', 'local:source', 's3:target']);
});

test('can chain all methods', function () {
    $command = RcloneCommandBuilder::create('/usr/bin/rclone', 'sync')
        ->addBaseOptions(['--verbose'])
        ->addOption('transfers', 10)
        ->addProgressOption(true)
        ->addSourceAndTarget('local:source', 's3:target')
        ->build();

    expect($command)->toEqual([
        '/usr/bin/rclone',
        'sync',
        '--verbose',
        '--transfers=10',
        '--progress',
        'local:source',
        's3:target',
    ]);
});
