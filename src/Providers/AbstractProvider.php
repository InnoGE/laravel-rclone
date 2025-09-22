<?php

namespace InnoGE\LaravelRclone\Providers;

use Illuminate\Support\Str;
use InnoGE\LaravelRclone\Contracts\ProviderInterface;
use InnoGE\LaravelRclone\Exceptions\InvalidConfigurationException;

abstract class AbstractProvider implements ProviderInterface
{
    /**
     * Build environment variables for the given disk configuration.
     *
     * @throws InvalidConfigurationException
     */
    public function buildEnvironment(string $diskName, array $config): array
    {
        $this->validateConfiguration($config);

        $upperDiskName = Str::upper($diskName);
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
     *
     * @throws InvalidConfigurationException
     */
    public function validateConfiguration(array $config): void
    {
        if (($config['driver'] ?? null) !== $this->getDriver()) {
            throw InvalidConfigurationException::driverMismatch(
                $this->getDriver(),
                $config['driver'] ?? 'null'
            );
        }

        $this->validateProviderSpecificConfiguration($config);
    }

    /**
     * Validate provider-specific configuration.
     * Override this method in concrete providers to add specific validation.
     */
    protected function validateProviderSpecificConfiguration(array $config): void
    {
        // Default implementation does nothing
    }

    /**
     * Validate that required fields are present and not empty.
     *
     * @throws InvalidConfigurationException
     */
    protected function validateRequiredFields(array $config, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                throw InvalidConfigurationException::missingField($this->getDriver(), $field);
            }
        }
    }

    /**
     * Get a configuration value with an optional default.
     */
    protected function getConfigValue(array $config, string $key, mixed $default = null): mixed
    {
        return $config[$key] ?? $default;
    }

    /**
     * Build the remote path for this provider.
     * Default implementation returns diskName:path format.
     */
    public function buildRemotePath(string $diskName, string $path, array $config): string
    {
        return $diskName.':'.Str::ltrim($path, '/');
    }
}
