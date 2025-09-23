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

    expect($env)->toEqual([
        'RCLONE_CONFIG_FTP_TEST_TYPE' => 'ftp',
        'RCLONE_CONFIG_FTP_TEST_HOST' => 'ftp.example.com',
        'RCLONE_CONFIG_FTP_TEST_USER' => 'testuser',
        'RCLONE_CONFIG_FTP_TEST_PASS' => 'testpass',
        'RCLONE_CONFIG_FTP_TEST_PORT' => '2121',
    ]);
});

test('uses default values when not specified', function () {
    $provider = new FtpProvider;

    $env = $provider->buildEnvironment('ftp_test', [
        'driver' => 'ftp',
    ]);

    expect($env)->toEqual([
        'RCLONE_CONFIG_FTP_TEST_TYPE' => 'ftp',
        'RCLONE_CONFIG_FTP_TEST_HOST' => '',
        'RCLONE_CONFIG_FTP_TEST_USER' => '',
        'RCLONE_CONFIG_FTP_TEST_PASS' => '',
        'RCLONE_CONFIG_FTP_TEST_PORT' => '21',
    ]);
});

test('has correct driver name', function () {
    $provider = new FtpProvider;

    expect($provider->getDriver())->toBe('ftp');
});
