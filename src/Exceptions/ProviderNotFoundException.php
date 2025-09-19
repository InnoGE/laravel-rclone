<?php

declare(strict_types=1);

namespace InnoGE\LaravelRclone\Exceptions;

class ProviderNotFoundException extends RcloneException
{
    public static function forDriver(string $driver): self
    {
        return new self("No provider found for driver: {$driver}");
    }
}
