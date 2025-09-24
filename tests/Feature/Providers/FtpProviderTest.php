<?php

declare(strict_types=1);

use InnoGE\LaravelRclone\Providers\FtpProvider;

test('builds environment variables correctly', function () {
    $provider = new FtpProvider;

    $env = $provider->buildEnvironment('ftp_test', [
        'driver' => 'ftp',
        'host' => 'ftp.example.com',
        'username' => 'testuser',
        'password' => 'testpass',
        'port' => 2121,
    ]);

    expect($env)->toHaveKey('RCLONE_CONFIG_FTP_TEST_TYPE', 'ftp');
    expect($env)->toHaveKey('RCLONE_CONFIG_FTP_TEST_HOST', 'ftp.example.com');
    expect($env)->toHaveKey('RCLONE_CONFIG_FTP_TEST_USER', 'testuser');
    expect($env)->toHaveKey('RCLONE_CONFIG_FTP_TEST_PORT', '2121');
    expect($env)->toHaveKey('RCLONE_CONFIG_FTP_TEST_PROVIDER', 'Other');

    // Password should be obscured, so we just check it's not empty and not the original
    expect($env['RCLONE_CONFIG_FTP_TEST_PASS'])->not->toBeEmpty();
    expect($env['RCLONE_CONFIG_FTP_TEST_PASS'])->not->toBe('testpass');
});

test('uses default values when not specified', function () {
    $provider = new FtpProvider;

    $env = $provider->buildEnvironment('ftp_test', [
        'driver' => 'ftp',
    ]);

    expect($env)->toHaveKey('RCLONE_CONFIG_FTP_TEST_TYPE', 'ftp');
    expect($env)->toHaveKey('RCLONE_CONFIG_FTP_TEST_HOST', '');
    expect($env)->toHaveKey('RCLONE_CONFIG_FTP_TEST_USER', '');
    expect($env)->toHaveKey('RCLONE_CONFIG_FTP_TEST_PASS', '');
    expect($env)->toHaveKey('RCLONE_CONFIG_FTP_TEST_PORT', '21');
    expect($env)->toHaveKey('RCLONE_CONFIG_FTP_TEST_PROVIDER', 'Other');
});

test('has correct driver name', function () {
    $provider = new FtpProvider;

    expect($provider->getDriver())->toBe('ftp');
});
