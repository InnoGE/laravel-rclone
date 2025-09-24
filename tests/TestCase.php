<?php

namespace InnoGE\LaravelRclone\Tests;

use Illuminate\Support\Facades\Process;
use InnoGE\LaravelRclone\Facades\Rclone;
use InnoGE\LaravelRclone\RcloneServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock rclone commands if Process::fake() is available (Laravel 10.4+)
        // Otherwise, real rclone will be used (should be installed in CI)
        if (method_exists(Process::class, 'fake')) {
            Process::fake([
                'rclone obscure *' => Process::result(
                    output: 'mocked_obscured_password_123',
                    exitCode: 0
                ),
                'rclone version' => Process::result(
                    output: 'rclone v1.60.1',
                    exitCode: 0
                ),
                'rclone *' => Process::result(
                    output: "Transferred: 1024 / 1024 Bytes, 100%, 1.024kBytes/s, ETA 0s\nTransferred: 5 / 10, 50%\nElapsed time: 1.0s",
                    exitCode: 0
                ),
            ]);
        }

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
