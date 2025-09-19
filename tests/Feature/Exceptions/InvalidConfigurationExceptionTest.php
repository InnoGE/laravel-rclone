<?php

use InnoGE\LaravelRclone\Exceptions\InvalidConfigurationException;

describe('InvalidConfigurationException Static Methods', function () {
    test('can create missing field exception', function () {
        $exception = InvalidConfigurationException::missingField('s3', 'bucket');

        expect($exception)->toBeInstanceOf(InvalidConfigurationException::class);
        expect($exception->getMessage())->toBe("Missing required field 'bucket' for s3 provider");
    });

    test('can create invalid value exception with simple types', function () {
        $exception = InvalidConfigurationException::invalidValue('port', 'not_a_number', 'integer');

        expect($exception)->toBeInstanceOf(InvalidConfigurationException::class);
        expect($exception->getMessage())->toBe("Invalid value for 'port': got string, expected integer");
    });

    test('can create invalid value exception with array', function () {
        $exception = InvalidConfigurationException::invalidValue('config', [], 'string');

        expect($exception)->toBeInstanceOf(InvalidConfigurationException::class);
        expect($exception->getMessage())->toBe("Invalid value for 'config': got array, expected string");
    });

    test('can create invalid value exception with object', function () {
        $obj = new stdClass();
        $exception = InvalidConfigurationException::invalidValue('setting', $obj, 'array');

        expect($exception)->toBeInstanceOf(InvalidConfigurationException::class);
        expect($exception->getMessage())->toBe("Invalid value for 'setting': got stdClass, expected array");
    });

    test('can create driver mismatch exception', function () {
        $exception = InvalidConfigurationException::driverMismatch('s3', 'local');

        expect($exception)->toBeInstanceOf(InvalidConfigurationException::class);
        expect($exception->getMessage())->toBe("Driver mismatch: expected 's3', got 'local'");
    });
});