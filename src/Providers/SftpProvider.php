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

        // Ensure either password or key-based auth is configured
        if (empty($config['password']) && empty($config['key_file']) && empty($config['private_key'])) {
            throw InvalidConfigurationException::missingField(
                $this->getDriver(),
                'password, key_file, or private_key (authentication required)'
            );
        }
    }

    protected function buildProviderSpecificEnvironment(string $upperDiskName, array $config): array
    {
        $password = $this->getConfigValue($config, 'password', '');
        $obscuredPassword = $this->obscurePassword($password);

        $env = [
            "RCLONE_CONFIG_{$upperDiskName}_HOST" => $this->getConfigValue($config, 'host', ''),
            "RCLONE_CONFIG_{$upperDiskName}_USER" => $this->getConfigValue($config, 'username', ''),
            "RCLONE_CONFIG_{$upperDiskName}_PASS" => $obscuredPassword,
            "RCLONE_CONFIG_{$upperDiskName}_PORT" => (string) $this->getConfigValue($config, 'port', 22),
        ];

        // Add key-based authentication if configured
        if (!empty($config['key_file'])) {
            $env["RCLONE_CONFIG_{$upperDiskName}_KEY_FILE"] = $config['key_file'];
        }

        if (!empty($config['private_key'])) {
            $env["RCLONE_CONFIG_{$upperDiskName}_KEY_PEM"] = $config['private_key'];
        }

        return $env;
    }
}
