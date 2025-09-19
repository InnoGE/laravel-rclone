<?php

declare(strict_types=1);

namespace InnoGE\LaravelRclone\Support;

use InnoGE\LaravelRclone\Contracts\ProviderInterface;
use InnoGE\LaravelRclone\Exceptions\ProviderNotFoundException;

class ProviderRegistry
{
    /**
     * @var array<string, ProviderInterface>
     */
    private array $providers = [];

    /**
     * Register a provider for a specific driver.
     */
    public function register(ProviderInterface $provider): void
    {
        $this->providers[$provider->getDriver()] = $provider;
    }

    /**
     * Get a provider for the given driver.
     */
    public function getProvider(string $driver): ProviderInterface
    {
        if (! isset($this->providers[$driver])) {
            throw ProviderNotFoundException::forDriver($driver);
        }

        return $this->providers[$driver];
    }

    /**
     * Check if a provider exists for the given driver.
     */
    public function hasProvider(string $driver): bool
    {
        return isset($this->providers[$driver]);
    }

    /**
     * Get all registered providers.
     *
     * @return array<string, ProviderInterface>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get all supported driver names.
     *
     * @return array<string>
     */
    public function getSupportedDrivers(): array
    {
        return array_keys($this->providers);
    }
}
