<?php

use InnoGE\LaravelRclone\Exceptions\InvalidConfigurationException;
use InnoGE\LaravelRclone\Providers\SftpProvider;

describe('SftpProvider Validation', function () {
    test('validates required host field', function () {
        $provider = new SftpProvider();

        expect(fn () => $provider->validateConfiguration([
            'driver' => 'sftp',
            'username' => 'testuser',
            'password' => 'testpass'
        ]))->toThrow(InvalidConfigurationException::class);
    });

    test('validates port range', function () {
        $provider = new SftpProvider();

        // Test port too low
        expect(fn () => $provider->validateConfiguration([
            'driver' => 'sftp',
            'host' => 'test.com',
            'username' => 'testuser',
            'password' => 'testpass',
            'port' => 0
        ]))->toThrow(InvalidConfigurationException::class);

        // Test port too high
        expect(fn () => $provider->validateConfiguration([
            'driver' => 'sftp',
            'host' => 'test.com',
            'username' => 'testuser',
            'password' => 'testpass',
            'port' => 70000
        ]))->toThrow(InvalidConfigurationException::class);
    });

    test('validates hostname format', function () {
        $provider = new SftpProvider();

        expect(fn () => $provider->validateConfiguration([
            'driver' => 'sftp',
            'host' => '', // Empty host
            'username' => 'testuser',
            'password' => 'testpass'
        ]))->toThrow(InvalidConfigurationException::class);
    });


    test('accepts valid IP address as host', function () {
        $provider = new SftpProvider();

        // Should not throw exception with valid IP
        $provider->validateConfiguration([
            'driver' => 'sftp',
            'host' => '192.168.1.1',
            'username' => 'testuser',
            'password' => 'testpass'
        ]);

        expect(true)->toBeTrue();
    });

    test('accepts valid domain as host', function () {
        $provider = new SftpProvider();

        // Should not throw exception with valid domain
        $provider->validateConfiguration([
            'driver' => 'sftp',
            'host' => 'sftp.example.com',
            'username' => 'testuser',
            'password' => 'testpass'
        ]);

        expect(true)->toBeTrue();
    });

    test('accepts key file authentication', function () {
        $provider = new SftpProvider();

        // Should not throw exception with key_file
        $provider->validateConfiguration([
            'driver' => 'sftp',
            'host' => 'test.com',
            'username' => 'testuser',
            'key_file' => '/path/to/key'
        ]);

        expect(true)->toBeTrue();
    });

    test('accepts private key authentication', function () {
        $provider = new SftpProvider();

        // Should not throw exception with private_key
        $provider->validateConfiguration([
            'driver' => 'sftp',
            'host' => 'test.com',
            'username' => 'testuser',
            'private_key' => 'private-key-content'
        ]);

        expect(true)->toBeTrue();
    });


    test('builds environment with all configuration values', function () {
        $provider = new SftpProvider();

        $env = $provider->buildEnvironment('sftp_test', [
            'driver' => 'sftp',
            'host' => 'sftp.example.com',
            'username' => 'testuser',
            'password' => 'testpass',
            'port' => 2222
        ]);

        expect($env)->toEqual([
            'RCLONE_CONFIG_SFTP_TEST_TYPE' => 'sftp',
            'RCLONE_CONFIG_SFTP_TEST_HOST' => 'sftp.example.com',
            'RCLONE_CONFIG_SFTP_TEST_USER' => 'testuser',
            'RCLONE_CONFIG_SFTP_TEST_PASS' => 'testpass',
            'RCLONE_CONFIG_SFTP_TEST_PORT' => '2222'
        ]);
    });

    test('validates hostname with various valid formats', function () {
        $provider = new SftpProvider();

        // Test with valid domain
        $provider->validateConfiguration([
            'driver' => 'sftp',
            'host' => 'sftp.example.com',
            'username' => 'testuser',
            'password' => 'testpass'
        ]);

        // Test with valid IP
        $provider->validateConfiguration([
            'driver' => 'sftp',
            'host' => '192.168.1.100',
            'username' => 'testuser',
            'password' => 'testpass'
        ]);

        expect(true)->toBeTrue();
    });

    test('validates authentication with various methods', function () {
        $provider = new SftpProvider();

        // Test with password authentication
        $provider->validateConfiguration([
            'driver' => 'sftp',
            'host' => 'test.com',
            'username' => 'testuser',
            'password' => 'testpass'
        ]);

        // Test with key file authentication
        $provider->validateConfiguration([
            'driver' => 'sftp',
            'host' => 'test.com',
            'username' => 'testuser',
            'key_file' => '/path/to/key'
        ]);

        // Test with private key authentication
        $provider->validateConfiguration([
            'driver' => 'sftp',
            'host' => 'test.com',
            'username' => 'testuser',
            'private_key' => 'key-content'
        ]);

        expect(true)->toBeTrue();
    });

});