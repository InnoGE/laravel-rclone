<?php

use InnoGE\LaravelRclone\Exceptions\InvalidConfigurationException;
use InnoGE\LaravelRclone\Providers\AbstractProvider;

    test('validateIntegerField method works correctly', function () {
        $provider = new class extends AbstractProvider {
            public function getDriver(): string { return 'test'; }
            protected function buildProviderSpecificEnvironment(string $upperDiskName, array $config): array {
                return [];
            }
            public function testValidateIntegerField(array $config, string $field, int $min = 1, int $max = PHP_INT_MAX): void {
                $this->validateIntegerField($config, $field, $min, $max);
            }
        };

        // Test with valid integer
        $provider->testValidateIntegerField(['port' => 22], 'port', 1, 65535);
        expect(true)->toBeTrue(); // Should not throw

        // Test with invalid type
        expect(fn () => $provider->testValidateIntegerField(['port' => 'not-int'], 'port'))
            ->toThrow(InvalidConfigurationException::class, 'integer');

        // Test below minimum
        expect(fn () => $provider->testValidateIntegerField(['port' => 0], 'port', 1, 65535))
            ->toThrow(InvalidConfigurationException::class, 'between 1 and 65535');

        // Test above maximum
        expect(fn () => $provider->testValidateIntegerField(['port' => 70000], 'port', 1, 65535))
            ->toThrow(InvalidConfigurationException::class, 'between 1 and 65535');

        // Test with missing field (should not throw)
        $provider->testValidateIntegerField([], 'missing_field');
        expect(true)->toBeTrue();
    });

    test('validateRequiredFields method works correctly', function () {
        $provider = new class extends AbstractProvider {
            public function getDriver(): string { return 'test'; }
            protected function buildProviderSpecificEnvironment(string $upperDiskName, array $config): array {
                return [];
            }
            public function testValidateRequiredFields(array $config, array $requiredFields): void {
                $this->validateRequiredFields($config, $requiredFields);
            }
        };

        // Test with all required fields present
        $provider->testValidateRequiredFields(
            ['host' => 'test.com', 'port' => 22],
            ['host', 'port']
        );
        expect(true)->toBeTrue();

        // Test with missing field
        expect(fn () => $provider->testValidateRequiredFields(['host' => 'test.com'], ['host', 'port']))
            ->toThrow(InvalidConfigurationException::class, 'port');

        // Test with empty field
        expect(fn () => $provider->testValidateRequiredFields(['host' => '', 'port' => 22], ['host']))
            ->toThrow(InvalidConfigurationException::class, 'host');
    });

    test('validateProviderSpecificConfiguration is called', function () {
        $called = false;

        $provider = new class($called) extends AbstractProvider {
            public function __construct(private bool &$called) {}

            public function getDriver(): string { return 'test'; }

            protected function buildProviderSpecificEnvironment(string $upperDiskName, array $config): array {
                return [];
            }

            protected function validateProviderSpecificConfiguration(array $config): void {
                $this->called = true;
            }
        };

        $provider->validateConfiguration(['driver' => 'test']);
        expect($called)->toBeTrue();
    });

    test('buildEnvironment creates proper structure', function () {
        $provider = new class extends AbstractProvider {
            public function getDriver(): string { return 'test'; }

            protected function buildProviderSpecificEnvironment(string $upperDiskName, array $config): array {
                return [
                    "RCLONE_CONFIG_{$upperDiskName}_HOST" => $config['host'] ?? '',
                    "RCLONE_CONFIG_{$upperDiskName}_PORT" => (string)($config['port'] ?? 22),
                ];
            }
        };

        $env = $provider->buildEnvironment('my_disk', [
            'driver' => 'test',
            'host' => 'example.com',
            'port' => 2222
        ]);

        expect($env)->toEqual([
            'RCLONE_CONFIG_MY_DISK_TYPE' => 'test',
            'RCLONE_CONFIG_MY_DISK_HOST' => 'example.com',
            'RCLONE_CONFIG_MY_DISK_PORT' => '2222'
        ]);
    });

    test('getConfigValue helper method works', function () {
        $provider = new class extends AbstractProvider {
            public function getDriver(): string { return 'test'; }
            protected function buildProviderSpecificEnvironment(string $upperDiskName, array $config): array {
                return [];
            }
            public function testGetConfigValue(array $config, string $key, mixed $default = null): mixed {
                return $this->getConfigValue($config, $key, $default);
            }
        };

        expect($provider->testGetConfigValue(['key' => 'value'], 'key'))->toBe('value');
        expect($provider->testGetConfigValue([], 'missing', 'default'))->toBe('default');
        expect($provider->testGetConfigValue([], 'missing'))->toBeNull();
    });

    test('throws exception when driver mismatch occurs', function () {
        $provider = new class extends AbstractProvider {
            public function getDriver(): string { return 'expected_driver'; }
            protected function buildProviderSpecificEnvironment(string $upperDiskName, array $config): array {
                return [];
            }
            protected function validateProviderSpecificConfiguration(array $config): void {}
        };

        expect(fn () => $provider->validateConfiguration(['driver' => 'wrong_driver']))
            ->toThrow(InvalidConfigurationException::class);
    });

    test('throws exception when driver is null', function () {
        $provider = new class extends AbstractProvider {
            public function getDriver(): string { return 'expected_driver'; }
            protected function buildProviderSpecificEnvironment(string $upperDiskName, array $config): array {
                return [];
            }
            protected function validateProviderSpecificConfiguration(array $config): void {}
        };

        expect(fn () => $provider->validateConfiguration([]))
            ->toThrow(InvalidConfigurationException::class);
    });
