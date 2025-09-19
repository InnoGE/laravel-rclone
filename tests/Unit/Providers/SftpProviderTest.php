<?php

use InnoGE\LaravelRclone\Providers\SftpProvider;

it('has correct driver name', function () {
    $provider = new SftpProvider;

    expect($provider->getDriver())->toBe('sftp');
});

it('builds correct environment variables', function () {
    $provider = new SftpProvider;
    $config = [
        'driver' => 'sftp',
        'host' => 'sftp.example.com',
        'username' => 'testuser',
        'password' => 'testpass',
        'port' => 22,
    ];

    $env = $provider->buildEnvironment('sftp_disk', $config);

    expect($env)
        ->toHaveKey('RCLONE_CONFIG_SFTP_DISK_TYPE', 'sftp')
        ->toHaveKey('RCLONE_CONFIG_SFTP_DISK_HOST', 'sftp.example.com')
        ->toHaveKey('RCLONE_CONFIG_SFTP_DISK_USER', 'testuser')
        ->toHaveKey('RCLONE_CONFIG_SFTP_DISK_PASS', 'testpass')
        ->toHaveKey('RCLONE_CONFIG_SFTP_DISK_PORT', '22');
});

it('uses default values when not specified', function () {
    $provider = new SftpProvider;
    $config = [
        'driver' => 'sftp',
    ];

    $env = $provider->buildEnvironment('sftp_disk', $config);

    expect($env)
        ->toHaveKey('RCLONE_CONFIG_SFTP_DISK_TYPE', 'sftp')
        ->toHaveKey('RCLONE_CONFIG_SFTP_DISK_HOST', '')
        ->toHaveKey('RCLONE_CONFIG_SFTP_DISK_USER', '')
        ->toHaveKey('RCLONE_CONFIG_SFTP_DISK_PASS', '')
        ->toHaveKey('RCLONE_CONFIG_SFTP_DISK_PORT', '22');
});

it('handles custom port configuration', function () {
    $provider = new SftpProvider;
    $config = [
        'driver' => 'sftp',
        'host' => 'custom.sftp.com',
        'username' => 'user',
        'password' => 'pass',
        'port' => 2222,
    ];

    $env = $provider->buildEnvironment('custom_sftp', $config);

    expect($env)->toHaveKey('RCLONE_CONFIG_CUSTOM_SFTP_PORT', '2222');
});

it('throws exception for wrong driver', function () {
    $provider = new SftpProvider;
    $config = [
        'driver' => 'ftp',
    ];

    expect(fn () => $provider->buildEnvironment('sftp_disk', $config))
        ->toThrow(InvalidArgumentException::class, "Driver mismatch: expected 'sftp', got 'ftp'");
});