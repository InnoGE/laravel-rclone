<?php

use InnoGE\LaravelRclone\Contracts\RcloneInterface;
use InnoGE\LaravelRclone\Facades\Rclone;

it('facade resolves to rclone interface', function () {
    $manager = Rclone::getFacadeRoot();

    expect($manager)->toBeInstanceOf(RcloneInterface::class);
});

it('facade forwards calls to manager instance', function () {
    $result = Rclone::source('s3_test', '/test/path');

    expect($result)->toBeInstanceOf(RcloneInterface::class);
});

it('facade can chain method calls', function () {
    $result = Rclone::source('s3_test', '/foo/bar/baz')
        ->target('local_test', '/backup/foo')
        ->withProgress()
        ->transfers(16);

    expect($result)->toBeInstanceOf(RcloneInterface::class);
});

it('facade supports fluent configuration', function () {
    $result = Rclone::source('s3_test', '/data')
        ->target('local_test', '/backup')
        ->withProgress(true)
        ->transfers(32)
        ->checkers(16)
        ->retries(5)
        ->statInterval(10)
        ->option('bandwidth', '100M');

    expect($result)->toBeInstanceOf(RcloneInterface::class);
});
