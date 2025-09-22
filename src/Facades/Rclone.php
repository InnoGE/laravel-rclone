<?php

namespace InnoGE\LaravelRclone\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use InnoGE\LaravelRclone\Contracts\RcloneInterface;
use InnoGE\LaravelRclone\Support\ProcessResult;

/**
 * @method static RcloneInterface source(string $disk, string $path = '/')
 * @method static RcloneInterface target(string $disk, string $path = '/')
 * @method static RcloneInterface withProgress(bool $progress = true)
 * @method static RcloneInterface transfers(int $transfers)
 * @method static RcloneInterface checkers(int $checkers)
 * @method static RcloneInterface retries(int $retries)
 * @method static RcloneInterface statInterval(int $seconds)
 * @method static RcloneInterface getOutputUsing(Closure $callback)
 * @method static RcloneInterface option(string $key, mixed $value)
 * @method static ProcessResult sync()
 * @method static ProcessResult copy()
 * @method static ProcessResult move()
 *
 * @see \InnoGE\LaravelRclone\Support\RcloneManager
 */
class Rclone extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'rclone';
    }
}
