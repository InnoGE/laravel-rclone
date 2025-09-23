<?php

declare(strict_types=1);

namespace InnoGE\LaravelRclone\Tests;

use InnoGE\LaravelRclone\Facades\Rclone;
use InnoGE\LaravelRclone\RcloneServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up test filesystem configuration
        config()->set('filesystems.disks.s3_test', [
            'driver' => 's3',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'region' => 'eu-west-1',
            'bucket' => 'test-bucket',
        ]);

        config()->set('filesystems.disks.local_test', [
            'driver' => 'local',
            'root' => storage_path('app/test'),
        ]);

        config()->set('filesystems.disks.sftp_test', [
            'driver' => 'sftp',
            'host' => 'test.example.com',
            'username' => 'testuser',
            'password' => 'testpass',
            'port' => 22,
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            RcloneServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Rclone' => Rclone::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('rclone.timeout', 120);
        $app['config']->set('rclone.base_options', ['--dry-run']);
    }
}
