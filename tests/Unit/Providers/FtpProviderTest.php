<?php

use InnoGE\LaravelRclone\Providers\FtpProvider;

it('has correct driver name', function () {
    $provider = new FtpProvider;

    expect($provider->getDriver())->toBe('ftp');
});

it('builds correct environment variables', function () {
    $provider = new FtpProvider;
    $config = [
        'driver' => 'ftp',
        'host' => 'ftp.example.com',
        'username' => 'ftpuser',
        'password' => 'ftppass',
        'port' => 21,
    ];

    $env = $provider->buildEnvironment('ftp_disk', $config);

    expect($env)
        ->toHaveKey('RCLONE_CONFIG_FTP_DISK_TYPE', 'ftp')
        ->toHaveKey('RCLONE_CONFIG_FTP_DISK_HOST', 'ftp.example.com')
        ->toHaveKey('RCLONE_CONFIG_FTP_DISK_USER', 'ftpuser')
        ->toHaveKey('RCLONE_CONFIG_FTP_DISK_PASS', 'ftppass')
        ->toHaveKey('RCLONE_CONFIG_FTP_DISK_PORT', '21');
});

it('uses default values when not specified', function () {
    $provider = new FtpProvider;
    $config = [
        'driver' => 'ftp',
    ];

    $env = $provider->buildEnvironment('ftp_disk', $config);

    expect($env)
        ->toHaveKey('RCLONE_CONFIG_FTP_DISK_TYPE', 'ftp')
        ->toHaveKey('RCLONE_CONFIG_FTP_DISK_HOST', '')
        ->toHaveKey('RCLONE_CONFIG_FTP_DISK_USER', '')
        ->toHaveKey('RCLONE_CONFIG_FTP_DISK_PASS', '')
        ->toHaveKey('RCLONE_CONFIG_FTP_DISK_PORT', '21');
});

it('handles custom port configuration', function () {
    $provider = new FtpProvider;
    $config = [
        'driver' => 'ftp',
        'host' => 'custom.ftp.com',
        'username' => 'user',
        'password' => 'pass',
        'port' => 2121,
    ];

    $env = $provider->buildEnvironment('custom_ftp', $config);

    expect($env)->toHaveKey('RCLONE_CONFIG_CUSTOM_FTP_PORT', '2121');
});

it('throws exception for wrong driver', function () {
    $provider = new FtpProvider;
    $config = [
        'driver' => 'sftp',
    ];

    expect(fn () => $provider->buildEnvironment('ftp_disk', $config))
        ->toThrow(InvalidArgumentException::class, "Driver mismatch: expected 'ftp', got 'sftp'");
});