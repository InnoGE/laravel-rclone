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

    expect($env)->toHaveKey('RCLONE_CONFIG_SFTP_TEST_TYPE', 'sftp');
    expect($env)->toHaveKey('RCLONE_CONFIG_SFTP_TEST_HOST', 'sftp.example.com');
    expect($env)->toHaveKey('RCLONE_CONFIG_SFTP_TEST_USER', 'testuser');
    expect($env)->toHaveKey('RCLONE_CONFIG_SFTP_TEST_PORT', '2222');
    expect($env)->toHaveKey('RCLONE_CONFIG_SFTP_TEST_PROVIDER', 'Other');

    // Password should be obscured, so we just check it's not empty and not the original
    expect($env['RCLONE_CONFIG_SFTP_TEST_PASS'])->not->toBeEmpty();
    expect($env['RCLONE_CONFIG_SFTP_TEST_PASS'])->not->toBe('testpass');
});

test('builds environment with key file authentication', function () {
    $provider = new SftpProvider;

    $env = $provider->buildEnvironment('sftp_test', [
        'driver' => 'sftp',
        'host' => 'sftp.example.com',
        'username' => 'testuser',
        'key_file' => '/path/to/key',
        'port' => 2222,
    ]);

    expect($env)->toHaveKey('RCLONE_CONFIG_SFTP_TEST_KEY_FILE', '/path/to/key');
    expect($env)->toHaveKey('RCLONE_CONFIG_SFTP_TEST_PASS', ''); // Empty password when using key
});

test('builds environment with private key authentication', function () {
    $provider = new SftpProvider;

    $env = $provider->buildEnvironment('sftp_test', [
        'driver' => 'sftp',
        'host' => 'sftp.example.com',
        'username' => 'testuser',
        'private_key' => 'private-key-content',
        'port' => 2222,
    ]);

    expect($env)->toHaveKey('RCLONE_CONFIG_SFTP_TEST_KEY_PEM', 'private-key-content');
    expect($env)->toHaveKey('RCLONE_CONFIG_SFTP_TEST_PASS', ''); // Empty password when using key
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
