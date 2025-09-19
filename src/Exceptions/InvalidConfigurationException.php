<?php

namespace InnoGE\LaravelRclone\Exceptions;

class InvalidConfigurationException extends RcloneException
{
    public static function missingField(string $provider, string $field): self
    {
        return new self("Missing required field '{$field}' for {$provider} provider");
    }

    public static function invalidValue(string $field, mixed $value, string $expected): self
    {
        $actualType = get_debug_type($value);
        return new self("Invalid value for '{$field}': got {$actualType}, expected {$expected}");
    }

    public static function driverMismatch(string $expected, string $actual): self
    {
        return new self("Driver mismatch: expected '{$expected}', got '{$actual}'");
    }
}