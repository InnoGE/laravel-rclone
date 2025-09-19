<?php

declare(strict_types=1);

namespace InnoGE\LaravelRclone\Support;

class ProcessResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly int $exitCode,
        public readonly string $output,
        public readonly string $errorOutput,
        public readonly array $stats
    ) {}

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function failed(): bool
    {
        return ! $this->successful;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getErrorOutput(): string
    {
        return $this->errorOutput;
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function getTransferredFiles(): int
    {
        return $this->stats['transferred_files'] ?? 0;
    }

    public function getTransferredBytes(): int
    {
        return $this->stats['transferred_bytes'] ?? 0;
    }

    public function getErrors(): int
    {
        return $this->stats['errors'] ?? 0;
    }

    public function getStatValue(string $key, mixed $default = 0): mixed
    {
        return $this->stats[$key] ?? $default;
    }
}
