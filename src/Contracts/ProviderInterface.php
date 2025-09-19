<?php

declare(strict_types=1);

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
}
