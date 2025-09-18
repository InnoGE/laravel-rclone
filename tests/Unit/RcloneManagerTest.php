<?php

use InnoGE\LaravelRclone\Contracts\RcloneInterface;
use InnoGE\LaravelRclone\Support\RcloneManager;

it('implements rclone interface', function () {
    $manager = app(RcloneInterface::class);

    expect($manager)->toBeInstanceOf(RcloneInterface::class);
    expect($manager)->toBeInstanceOf(RcloneManager::class);
});

it('can set source disk and path', function () {
    $manager = app(RcloneInterface::class);
    $result = $manager->source('s3_test', '/foo/bar');

    expect($result)->toBeInstanceOf(RcloneInterface::class);
});

it('can set target disk and path', function () {
    $manager = app(RcloneInterface::class);
    $result = $manager->target('local_test', '/backup');

    expect($result)->toBeInstanceOf(RcloneInterface::class);
});

it('can enable progress', function () {
    $manager = app(RcloneInterface::class);
    $result = $manager->withProgress(true);

    expect($result)->toBeInstanceOf(RcloneInterface::class);
});

it('can set transfers', function () {
    $manager = app(RcloneInterface::class);
    $result = $manager->transfers(16);

    expect($result)->toBeInstanceOf(RcloneInterface::class);
});

it('can set checkers', function () {
    $manager = app(RcloneInterface::class);
    $result = $manager->checkers(8);

    expect($result)->toBeInstanceOf(RcloneInterface::class);
});

it('can set retries', function () {
    $manager = app(RcloneInterface::class);
    $result = $manager->retries(5);

    expect($result)->toBeInstanceOf(RcloneInterface::class);
});

it('can set stat interval', function () {
    $manager = app(RcloneInterface::class);
    $result = $manager->statInterval(10);

    expect($result)->toBeInstanceOf(RcloneInterface::class);
});

it('can set output callback', function () {
    $manager = app(RcloneInterface::class);
    $callback = function ($type, $buffer) {
        echo $buffer;
    };
    $result = $manager->getOutputUsing($callback);

    expect($result)->toBeInstanceOf(RcloneInterface::class);
});

it('can set custom options', function () {
    $manager = app(RcloneInterface::class);
    $result = $manager->option('bandwidth', '10M');

    expect($result)->toBeInstanceOf(RcloneInterface::class);
});

it('can chain methods fluently', function () {
    $manager = app(RcloneInterface::class);

    $result = $manager
        ->source('s3_test', '/foo/bar/baz')
        ->target('local_test', '/backup/foo')
        ->withProgress()
        ->transfers(16)
        ->checkers(8)
        ->retries(3)
        ->statInterval(5)
        ->option('bandwidth', '50M')
        ->getOutputUsing(function ($type, $buffer) {
            // Output callback
        });

    expect($result)->toBeInstanceOf(RcloneInterface::class);
});

it('throws exception when source disk is not set', function () {
    $manager = app(RcloneInterface::class);
    $manager->target('local_test', '/backup');

    expect(fn () => $manager->sync())->toThrow(RuntimeException::class, 'Source disk is required');
});

it('throws exception when target disk is not set', function () {
    $manager = app(RcloneInterface::class);
    $manager->source('s3_test', '/foo');

    expect(fn () => $manager->sync())->toThrow(RuntimeException::class, 'Target disk is required');
});

it('uses Laravel filesystem configuration', function () {
    $manager = new class([], config('filesystems.disks')) extends RcloneManager
    {
        public function testBuildEnvironment(): array
        {
            $this->sourceDisk = 's3_test';
            $this->targetDisk = 's3_test';

            return $this->buildEnvironment();
        }
    };

    $env = $manager->testBuildEnvironment();

    expect($env)->toHaveKey('RCLONE_CONFIG_S3_TEST_TYPE', 's3')
        ->toHaveKey('RCLONE_CONFIG_S3_TEST_ACCESS_KEY_ID', 'test-key')
        ->toHaveKey('RCLONE_CONFIG_S3_TEST_SECRET_ACCESS_KEY', 'test-secret')
        ->toHaveKey('RCLONE_CONFIG_S3_TEST_REGION', 'eu-west-1')
        ->toHaveKey('RCLONE_CONFIG_S3_TEST_BUCKET', 'test-bucket');
});
