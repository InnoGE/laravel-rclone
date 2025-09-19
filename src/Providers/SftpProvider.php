<?php

namespace InnoGE\LaravelRclone\Providers;

use InnoGE\LaravelRclone\Exceptions\InvalidConfigurationException;

class SftpProvider extends AbstractProvider
{
    public function getDriver(): string
    {
        return 'sftp';
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function validateProviderSpecificConfiguration(array $config): void
    {
        $this->validateRequiredFields($config, ['host']);

        // Validate port if provided
        $this->validateIntegerField($config, 'port', 1, 65535);

        // Validate host format
        if (isset($config['host'])) {
            $host = $config['host'];
            if (empty($host) || !$this->isValidHostname($host)) {
                throw InvalidConfigurationException::invalidValue(
                    'host',
                    $host,
                    'valid hostname or IP address'
                );
            }
        }

        // Ensure either password or key-based auth is configured
        if (empty($config['password']) && empty($config['key_file']) && empty($config['private_key'])) {
            throw InvalidConfigurationException::missingField(
                $this->getDriver(),
                'password, key_file, or private_key (authentication required)'
            );
        }
    }

    private function isValidHostname(string $host): bool
    {
        // Basic hostname/IP validation
        return filter_var($host, FILTER_VALIDATE_IP) !== false ||
               filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }

    protected function buildProviderSpecificEnvironment(string $upperDiskName, array $config): array
    {
        return [
            "RCLONE_CONFIG_{$upperDiskName}_HOST" => $this->getConfigValue($config, 'host', ''),
            "RCLONE_CONFIG_{$upperDiskName}_USER" => $this->getConfigValue($config, 'username', ''),
            "RCLONE_CONFIG_{$upperDiskName}_PASS" => $this->getConfigValue($config, 'password', ''),
            "RCLONE_CONFIG_{$upperDiskName}_PORT" => (string) $this->getConfigValue($config, 'port', 22),
        ];
    }
}
