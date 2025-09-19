<?php

declare(strict_types=1);

namespace InnoGE\LaravelRclone\Support;

use Illuminate\Support\Str;

final class StatsParser
{
    private const STATS_PATTERNS = [
        'transferred_files' => '/Transferred:\s+(\d+)\s+\/\s+\d+/',
        'total_files' => '/Transferred:\s+\d+\s+\/\s+(\d+)/',
        'transferred_bytes' => '/Transferred:\s+([\d,]+(?:\.\d+)?)\s*([KMGTPE]?)i?B/',
        'transfer_rate' => '/Transferred:\s+.*,\s*([\d.]+)\s*([KMGTPE]?)i?B\/s/',
        'errors' => '/Errors:\s+(\d+)/',
        'checks' => '/Checks:\s+(\d+)/',
        'deletes' => '/Deleted:\s+(\d+)/',
        'renames' => '/Renamed:\s+(\d+)/',
        'elapsed_time' => '/Elapsed time:\s+(\d+(?:\.\d+)?)s/',
        'percentage' => '/(\d+)%/',
        'eta' => '/ETA\s+(\d+(?::\d+)*)/i',
    ];

    private const BYTE_UNITS = [
        '' => 1,
        'K' => 1024,
        'M' => 1024 ** 2,
        'G' => 1024 ** 3,
        'T' => 1024 ** 4,
        'P' => 1024 ** 5,
        'E' => 1024 ** 6,
    ];

    public static function parse(string $output): array
    {
        $parser = new self;

        return $parser->parseOutput($output);
    }

    private function parseOutput(string $output): array
    {
        $stats = $this->getDefaultStats();

        foreach (self::STATS_PATTERNS as $key => $pattern) {
            $value = $this->extractValue($output, $pattern, $key);
            if ($value !== null) {
                $stats[$key] = $value;
            }
        }

        // Calculate additional metrics
        $stats['success_rate'] = $this->calculateSuccessRate($stats);
        $stats['transfer_speed_mbps'] = $this->calculateTransferSpeedMbps($stats);

        return $stats;
    }

    private function extractValue(string $output, string $pattern, string $key): mixed
    {
        if (! preg_match($pattern, $output, $matches)) {
            return null;
        }

        return match ($key) {
            'transferred_bytes', 'transfer_rate' => $this->parseByteValue($matches),
            'elapsed_time' => (float) $matches[1],
            'eta' => $this->parseEta($matches[1]),
            'percentage' => (int) $matches[1],
            default => (int) $matches[1],
        };
    }

    private function parseByteValue(array $matches): int
    {
        $value = (float) Str::replace(',', '', $matches[1]);
        $unit = $matches[2] ?? '';

        return (int) ($value * (self::BYTE_UNITS[$unit] ?? 1));
    }

    private function parseEta(string $eta): int
    {
        $parts = explode(':', $eta);
        $seconds = 0;

        foreach (array_reverse($parts) as $index => $part) {
            $seconds += (int) $part * (60 ** $index);
        }

        return $seconds;
    }

    private function calculateSuccessRate(array $stats): float
    {
        $total = $stats['total_files'] ?? 0;
        if ($total === 0) {
            return 100.0;
        }

        $errors = $stats['errors'] ?? 0;

        return round((($total - $errors) / $total) * 100, 2);
    }

    private function calculateTransferSpeedMbps(array $stats): float
    {
        $elapsedTime = $stats['elapsed_time'] ?? 0;
        if ($elapsedTime <= 0) {
            return 0.0;
        }

        $bytes = $stats['transferred_bytes'] ?? 0;
        if ($bytes <= 0) {
            return 0.0;
        }

        $bytesPerSecond = $bytes / $elapsedTime;
        $megabitsPerSecond = ($bytesPerSecond * 8) / (1024 ** 2);

        return round($megabitsPerSecond, 2);
    }

    private function getDefaultStats(): array
    {
        return [
            'transferred_files' => 0,
            'total_files' => 0,
            'transferred_bytes' => 0,
            'transfer_rate' => 0,
            'errors' => 0,
            'checks' => 0,
            'deletes' => 0,
            'renames' => 0,
            'elapsed_time' => 0.0,
            'percentage' => 0,
            'eta' => 0,
            'success_rate' => 100.0,
            'transfer_speed_mbps' => 0.0,
        ];
    }
}
