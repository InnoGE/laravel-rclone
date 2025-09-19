<?php

declare(strict_types=1);

namespace InnoGE\LaravelRclone\Providers;

use InnoGE\LaravelRclone\Exceptions\InvalidConfigurationException;

class S3Provider extends AbstractProvider
{
    public function getDriver(): string
    {
        return 's3';
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function validateProviderSpecificConfiguration(array $config): void
    {
        $this->validateRequiredFields($config, ['key', 'secret', 'region', 'bucket']);
    }

    protected function buildProviderSpecificEnvironment(string $upperDiskName, array $config): array
    {
        $env = [
            "RCLONE_CONFIG_{$upperDiskName}_ACCESS_KEY_ID" => $this->getConfigValue($config, 'key', ''),
            "RCLONE_CONFIG_{$upperDiskName}_SECRET_ACCESS_KEY" => $this->getConfigValue($config, 'secret', ''),
            "RCLONE_CONFIG_{$upperDiskName}_REGION" => $this->getConfigValue($config, 'region', 'us-east-1'),
            "RCLONE_CONFIG_{$upperDiskName}_BUCKET" => $this->getConfigValue($config, 'bucket', ''),
        ];

        // Optional endpoint configuration
        if (isset($config['endpoint'])) {
            $env["RCLONE_CONFIG_{$upperDiskName}_ENDPOINT"] = $config['endpoint'];
        }

        // Optional path style endpoint configuration
        if (isset($config['use_path_style_endpoint'])) {
            $env["RCLONE_CONFIG_{$upperDiskName}_FORCE_PATH_STYLE"] = $config['use_path_style_endpoint'] ? 'true' : 'false';
        }

        return $env;
    }
}
