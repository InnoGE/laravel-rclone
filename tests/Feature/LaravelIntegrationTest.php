<?php

use InnoGE\LaravelRclone\Contracts\RcloneInterface;
use InnoGE\LaravelRclone\Facades\Rclone;

it('can access rclone via service container', function () {
    $manager = app('rclone');

    expect($manager)->toBeInstanceOf(RcloneInterface::class);
});

it('can access rclone via interface binding', function () {
    $manager = app(RcloneInterface::class);

    expect($manager)->toBeInstanceOf(RcloneInterface::class);
});

it('facade uses Laravel filesystem configuration', function () {
    $result = Rclone::source('s3_test', '/documents')
        ->target('local_test', '/backup/documents');

    expect($result)->toBeInstanceOf(RcloneInterface::class);
});

it('can demonstrate Laravel-style usage patterns', function () {
    // Pattern 1: Direct facade usage
    $sync1 = Rclone::source('s3_test', 'data/backups')
        ->target('local_test', 'storage/backups')
        ->withProgress()
        ->transfers(32);

    // Pattern 2: Service container resolution
    $manager = app(RcloneInterface::class);
    $sync2 = $manager->source('local_test', '/uploads')
        ->target('s3_test', '/backup/uploads');

    // Pattern 3: Configuration chaining
    $sync3 = Rclone::source('sftp_test', 'remote/data')
        ->target('s3_test', 'archive/remote')
        ->withProgress(true)
        ->transfers(16)
        ->checkers(8)
        ->retries(3)
        ->statInterval(5)
        ->option('bandwidth', '50M');

    expect($sync1)->toBeInstanceOf(RcloneInterface::class);
    expect($sync2)->toBeInstanceOf(RcloneInterface::class);
    expect($sync3)->toBeInstanceOf(RcloneInterface::class);
});

it('uses configuration from Laravel config', function () {
    expect(config('rclone.timeout'))->toBe(120);
    expect(config('rclone.base_options'))->toContain('--dry-run');
});

it('integrates with Laravel filesystem disks', function () {
    $filesystemDisks = config('filesystems.disks');

    expect($filesystemDisks)->toHaveKey('s3_test');
    expect($filesystemDisks)->toHaveKey('local_test');
    expect($filesystemDisks)->toHaveKey('sftp_test');

    expect($filesystemDisks['s3_test']['driver'])->toBe('s3');
    expect($filesystemDisks['local_test']['driver'])->toBe('local');
    expect($filesystemDisks['sftp_test']['driver'])->toBe('sftp');
});

it('can handle different driver combinations', function () {
    // S3 to Local
    expect(function () {
        Rclone::source('s3_test', 'bucket/path')
            ->target('local_test', 'storage/backup')
            ->withProgress();
    })->not->toThrow(Exception::class);

    // Local to SFTP
    expect(function () {
        Rclone::source('local_test', 'storage/files')
            ->target('sftp_test', 'remote/backup')
            ->transfers(8);
    })->not->toThrow(Exception::class);

    // SFTP to S3
    expect(function () {
        Rclone::source('sftp_test', 'uploads')
            ->target('s3_test', 'backups/sftp')
            ->checkers(4);
    })->not->toThrow(Exception::class);
});
