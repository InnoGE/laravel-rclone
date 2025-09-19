<?php

namespace InnoGE\LaravelRclone\Providers;

use InnoGE\LaravelRclone\Contracts\ProviderInterface;
use InvalidArgumentException;

abstract class AbstractProvider implements ProviderInterface
{
    /**
     * Build environment variables for the given disk configuration.
     */
    public function buildEnvironment(string $diskName, array $config): array
    {
        $this->validateConfiguration($config);

        $upperDiskName = strtoupper($diskName);
        $env = [];

        $env["RCLONE_CONFIG_{$upperDiskName}_TYPE"] = $this->getDriver();

        return array_merge($env, $this->buildProviderSpecificEnvironment($upperDiskName, $config));
    }

    /**
     * Build provider-specific environment variables.
     */
    abstract protected function buildProviderSpecificEnvironment(string $upperDiskName, array $config): array;

    /**
     * Validate the configuration for this provider.
     */
    public function validateConfiguration(array $config): void
    {
        if (($config['driver'] ?? null) !== $this->getDriver()) {
            throw new InvalidArgumentException(
                "Driver mismatch: expected '{$this->getDriver()}', got '{$config['driver']}'"
            );
        }
    }

    /**
     * Get a configuration value with an optional default.
     */
    protected function getConfigValue(array $config, string $key, mixed $default = null): mixed
    {
        return $config[$key] ?? $default;
    }
}
