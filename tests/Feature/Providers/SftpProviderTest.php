<?php

declare(strict_types=1);

use InnoGE\LaravelRclone\Exceptions\InvalidConfigurationException;
use InnoGE\LaravelRclone\Providers\SftpProvider;

test('validates required host field', function () {
    $provider = new SftpProvider;

    expect(fn () => $provider->validateConfiguration([
        'driver' => 'sftp',
        'username' => 'testuser',
        'password' => 'testpass',
    ]))->toThrow(InvalidConfigurationException::class);
});

test('accepts key file authentication', function () {
    $provider = new SftpProvider;

    // Should not throw exception with key_file
    $provider->validateConfiguration([
        'driver' => 'sftp',
        'host' => 'test.com',
        'username' => 'testuser',
        'key_file' => '/path/to/key',
    ]);

    expect(true)->toBeTrue();
});

test('accepts private key authentication', function () {
    $provider = new SftpProvider;

    // Should not throw exception with private_key
    $provider->validateConfiguration([
        'driver' => 'sftp',
        'host' => 'test.com',
        'username' => 'testuser',
        'private_key' => 'private-key-content',
    ]);

    expect(true)->toBeTrue();
});

test('builds environment with all configuration values', function () {
    $provider = new SftpProvider;

    $env = $provider->buildEnvironment('sftp_test', [
        'driver' => 'sftp',
        'host' => 'sftp.example.com',
        'username' => 'testuser',
        'password' => 'testpass',
        'port' => 2222,
    ]);

    expect($env)->toEqual([
        'RCLONE_CONFIG_SFTP_TEST_TYPE' => 'sftp',
        'RCLONE_CONFIG_SFTP_TEST_HOST' => 'sftp.example.com',
        'RCLONE_CONFIG_SFTP_TEST_USER' => 'testuser',
        'RCLONE_CONFIG_SFTP_TEST_PASS' => 'testpass',
        'RCLONE_CONFIG_SFTP_TEST_PORT' => '2222',
    ]);
});

test('validates authentication throws exception when missing all methods', function () {
    $provider = new SftpProvider;

    try {
        $provider->validateConfiguration([
            'driver' => 'sftp',
            'host' => 'test.com',
            'username' => 'testuser',
            // Missing password, key_file, and private_key
        ]);
        expect(false)->toBeTrue(); // Should not reach here
    } catch (InvalidConfigurationException $e) {
        expect($e->getMessage())->toContain('authentication required');
    }
});
