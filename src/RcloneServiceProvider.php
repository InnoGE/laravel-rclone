<?php

declare(strict_types=1);

namespace InnoGE\LaravelRclone;

use Illuminate\Support\ServiceProvider;
use InnoGE\LaravelRclone\Contracts\RcloneInterface;
use InnoGE\LaravelRclone\Providers\FtpProvider;
use InnoGE\LaravelRclone\Providers\LocalProvider;
use InnoGE\LaravelRclone\Providers\S3Provider;
use InnoGE\LaravelRclone\Providers\SftpProvider;
use InnoGE\LaravelRclone\Support\ProviderRegistry;
use InnoGE\LaravelRclone\Support\RcloneManager;

class RcloneServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/rclone.php',
            'rclone'
        );

        $this->app->singleton(ProviderRegistry::class, function () {
            $registry = new ProviderRegistry;

            // Register default providers
            $registry->register(new LocalProvider);
            $registry->register(new S3Provider);
            $registry->register(new SftpProvider);
            $registry->register(new FtpProvider);

            return $registry;
        });

        $this->app->singleton(RcloneInterface::class, function ($app) {
            return new RcloneManager(
                $app['config']->get('rclone', []),
                $app['config']->get('filesystems.disks', []),
                $app->make(ProviderRegistry::class),
                $app->make('log')
            );
        });

        $this->app->alias(RcloneInterface::class, 'rclone');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/rclone.php' => config_path('rclone.php'),
            ], 'rclone-config');
        }
    }

    public function provides(): array
    {
        return [
            RcloneInterface::class,
            'rclone',
        ];
    }
}
