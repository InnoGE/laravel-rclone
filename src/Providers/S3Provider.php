<?php

namespace InnoGE\LaravelRclone\Providers;

use Illuminate\Support\Str;
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

        // Validate region format
        if (isset($config['region']) && !preg_match('/^[a-z0-9\-]+$/', $config['region'])) {
            throw InvalidConfigurationException::invalidValue(
                'region',
                $config['region'],
                'valid AWS region (e.g., us-east-1)'
            );
        }

        // Validate bucket name format (simplified AWS rules)
        if (isset($config['bucket'])) {
            $bucket = $config['bucket'];
            if (!preg_match('/^[a-z0-9.-]{3,63}$/', $bucket) ||
                Str::contains($bucket, '..') ||
                Str::startsWith($bucket, '.') ||
                Str::endsWith($bucket, '.')) {
                throw InvalidConfigurationException::invalidValue(
                    'bucket',
                    $bucket,
                    'valid S3 bucket name'
                );
            }
        }
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
