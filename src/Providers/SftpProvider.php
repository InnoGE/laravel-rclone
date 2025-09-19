<?php

namespace InnoGE\LaravelRclone\Providers;

class SftpProvider extends AbstractProvider
{
    public function getDriver(): string
    {
        return 'sftp';
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
