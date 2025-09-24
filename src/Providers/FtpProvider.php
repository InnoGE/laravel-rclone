<?php

namespace InnoGE\LaravelRclone\Providers;

class FtpProvider extends AbstractProvider
{
    public function getDriver(): string
    {
        return 'ftp';
    }

    protected function buildProviderSpecificEnvironment(string $upperDiskName, array $config): array
    {
        $password = $this->getConfigValue($config, 'password', '');
        $obscuredPassword = $this->obscurePassword($password);

        return [
            "RCLONE_CONFIG_{$upperDiskName}_HOST" => $this->getConfigValue($config, 'host', ''),
            "RCLONE_CONFIG_{$upperDiskName}_USER" => $this->getConfigValue($config, 'username', ''),
            "RCLONE_CONFIG_{$upperDiskName}_PASS" => $obscuredPassword,
            "RCLONE_CONFIG_{$upperDiskName}_PORT" => (string) $this->getConfigValue($config, 'port', 21),
        ];
    }
}
