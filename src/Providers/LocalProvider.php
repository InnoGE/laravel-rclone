<?php

namespace InnoGE\LaravelRclone\Providers;

class LocalProvider extends AbstractProvider
{
    public function getDriver(): string
    {
        return 'local';
    }

    protected function buildProviderSpecificEnvironment(string $upperDiskName, array $config): array
    {
        return [
            "RCLONE_CONFIG_{$upperDiskName}_ROOT" => $this->getConfigValue($config, 'root', '/'),
        ];
    }
}
