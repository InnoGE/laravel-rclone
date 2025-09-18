<?php

namespace InnoGE\LaravelRclone;

use Illuminate\Support\ServiceProvider;
use InnoGE\LaravelRclone\Contracts\RcloneInterface;
use InnoGE\LaravelRclone\Support\RcloneManager;

class RcloneServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/rclone.php',
            'rclone'
        );

        $this->app->singleton(RcloneInterface::class, function ($app) {
            return new RcloneManager(
                $app['config']->get('rclone', []),
                $app['config']->get('filesystems.disks', [])
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
