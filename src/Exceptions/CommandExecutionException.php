<?php

declare(strict_types=1);

namespace InnoGE\LaravelRclone\Exceptions;

class CommandExecutionException extends RcloneException
{
    public static function binaryNotFound(): self
    {
        return new self(
            'rclone binary not found. Please install rclone or set binary_path in configuration.'
        );
    }

    public static function invalidParameters(string $parameter, mixed $value): self
    {
        return new self("Invalid parameter '{$parameter}': {$value}");
    }

    public static function missingDisk(string $type): self
    {
        return new self("{$type} disk is required");
    }
}
