<?php

namespace InnoGE\LaravelRclone\Support;

use InnoGE\LaravelRclone\Exceptions\InvalidConfigurationException;

final class RcloneCommandBuilder
{
    private const OPTION_MAPPING = [
        'transfers' => ['template' => '--transfers=%d', 'min' => 1, 'max' => 100],
        'checkers' => ['template' => '--checkers=%d', 'min' => 1, 'max' => 100],
        'retries' => ['template' => '--retries=%d', 'min' => 0, 'max' => 10],
        'stat_interval' => ['template' => '--stats=%ds', 'min' => 1, 'max' => 3600],
    ];

    private array $command = [];

    private array $options = [];

    public function __construct(
        private readonly string $binary,
        private readonly string $operation
    ) {
        $this->command = [$this->binary, $this->operation];
    }

    public static function create(string $binary, string $operation): self
    {
        return new self($binary, $operation);
    }

    public function addBaseOptions(array $baseOptions): self
    {
        $this->command = array_merge($this->command, $baseOptions);

        return $this;
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function addOption(string $key, mixed $value): self
    {
        if ($key === 'progress') {
            return $this->addProgressOption($value);
        }

        if (! isset(self::OPTION_MAPPING[$key])) {
            // Custom options are added as-is
            $this->options[$key] = $value;

            return $this;
        }

        $config = self::OPTION_MAPPING[$key];
        $this->validateOptionValue($key, $value, $config);

        $this->command[] = sprintf($config['template'], $value);

        return $this;
    }

    public function addProgressOption(bool $enabled): self
    {
        if ($enabled) {
            $this->command[] = '--progress';
        }

        return $this;
    }

    public function addSourceAndTarget(string $source, string $target): self
    {
        $this->command[] = $source;
        $this->command[] = $target;

        return $this;
    }

    public function build(): array
    {
        $command = $this->command;

        // Add custom options
        foreach ($this->options as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $command[] = "--{$key}";
                }
            } elseif (is_string($value) || is_numeric($value)) {
                $command[] = "--{$key}={$value}";
            }
        }

        return $command;
    }

    /**
     * @throws InvalidConfigurationException
     */
    private function validateOptionValue(string $key, mixed $value, array $config): void
    {
        if (! is_int($value)) {
            throw InvalidConfigurationException::invalidValue($key, $value, 'integer');
        }

        if (isset($config['min']) && $value < $config['min']) {
            throw InvalidConfigurationException::invalidValue(
                $key,
                $value,
                "integer >= {$config['min']}"
            );
        }

        if (isset($config['max']) && $value > $config['max']) {
            throw InvalidConfigurationException::invalidValue(
                $key,
                $value,
                "integer <= {$config['max']}"
            );
        }
    }
}
