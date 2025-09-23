<?php

namespace InnoGE\LaravelRclone\Contracts;

interface ProviderInterface
{
    /**
     * Build environment variables for the given disk configuration.
     */
    public function buildEnvironment(string $diskName, array $config): array;

    /**
     * Get the driver name that this provider handles.
     */
    public function getDriver(): string;

    /**
     * Validate the configuration for this provider.
     */
    public function validateConfiguration(array $config): void;

    /**
     * Build the remote path for this provider.
     * Default implementation returns diskName:path format.
     */
    public function buildRemotePath(string $diskName, string $path, array $config): string;
}
