<?php

namespace InnoGE\LaravelRclone\Providers;

use Illuminate\Support\Str;

class LocalProvider extends AbstractProvider
{
    public function getDriver(): string
    {
        return 'local';
    }

    protected function buildProviderSpecificEnvironment(string $upperDiskName, array $config): array
    {
        return [];
    }

    public function buildRemotePath(string $diskName, string $path, array $config): string
    {
        return Str::of($config['root'] ?? '/')
            ->finish('/')
            ->append(Str::ltrim($path, '/'));
    }
}
