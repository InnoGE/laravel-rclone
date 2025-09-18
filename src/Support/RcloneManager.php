<?php

namespace InnoGE\LaravelRclone\Support;

use Closure;
use Illuminate\Support\Facades\Process;
use InnoGE\LaravelRclone\Contracts\RcloneInterface;
use InvalidArgumentException;
use RuntimeException;

class RcloneManager implements RcloneInterface
{
    protected array $config;

    protected array $filesystemDisks;

    protected string $sourceDisk = '';

    protected string $sourcePath = '/';

    protected string $targetDisk = '';

    protected string $targetPath = '/';

    protected array $options = [];

    protected ?Closure $outputCallback = null;

    public function __construct(array $config = [], array $filesystemDisks = [])
    {
        $this->config = array_merge([
            'binary_path' => null,
            'timeout' => 3600,
            'base_options' => [
                '--delete-after',
                '--fast-list',
                '--checksum',
            ],
            'defaults' => [
                'transfers' => 4,
                'checkers' => 8,
                'retries' => 3,
                'progress' => false,
                'stat_interval' => 1,
            ],
        ], $config);

        $this->filesystemDisks = $filesystemDisks;
        $this->options = $this->config['defaults'];
    }

    public function source(string $disk, string $path = '/'): self
    {
        $this->sourceDisk = $disk;
        $this->sourcePath = $this->normalizePath($path);

        return $this;
    }

    public function target(string $disk, string $path = '/'): self
    {
        $this->targetDisk = $disk;
        $this->targetPath = $this->normalizePath($path);

        return $this;
    }

    public function withProgress(bool $progress = true): self
    {
        $this->options['progress'] = $progress;

        return $this;
    }

    public function transfers(int $transfers): self
    {
        $this->options['transfers'] = $transfers;

        return $this;
    }

    public function checkers(int $checkers): self
    {
        $this->options['checkers'] = $checkers;

        return $this;
    }

    public function retries(int $retries): self
    {
        $this->options['retries'] = $retries;

        return $this;
    }

    public function statInterval(int $seconds): self
    {
        $this->options['stat_interval'] = $seconds;

        return $this;
    }

    public function getOutputUsing(Closure $callback): self
    {
        $this->outputCallback = $callback;

        return $this;
    }

    public function option(string $key, mixed $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    public function sync(): ProcessResult
    {
        return $this->executeCommand('sync');
    }

    public function copy(): ProcessResult
    {
        return $this->executeCommand('copy');
    }

    public function move(): ProcessResult
    {
        return $this->executeCommand('move');
    }

    protected function executeCommand(string $operation): ProcessResult
    {
        $this->validateConfiguration();

        $command = $this->buildCommand($operation);
        $environment = $this->buildEnvironment();

        $process = Process::timeout($this->config['timeout'])
            ->env($environment)
            ->run($command, function (string $type, string $buffer) {
                if ($this->outputCallback) {
                    ($this->outputCallback)($type, $buffer);
                }
            });

        return new ProcessResult(
            $process->successful(),
            $process->exitCode(),
            $process->output(),
            $process->errorOutput(),
            $this->parseStats($process->errorOutput())
        );
    }

    protected function buildCommand(string $operation): array
    {
        $binary = $this->config['binary_path'] ?? $this->findRcloneBinary();

        $command = [$binary, $operation];

        // Add base options
        $command = array_merge($command, $this->config['base_options']);

        // Add dynamic options
        foreach ($this->options as $key => $value) {
            switch ($key) {
                case 'transfers':
                    $command[] = "--transfers={$value}";
                    break;
                case 'checkers':
                    $command[] = "--checkers={$value}";
                    break;
                case 'retries':
                    $command[] = "--retries={$value}";
                    break;
                case 'stat_interval':
                    $command[] = "--stats={$value}s";
                    break;
                case 'progress':
                    if ($value) {
                        $command[] = '--progress';
                    }
                    break;
            }
        }

        // Add source and target
        $command[] = $this->buildDiskPath($this->sourceDisk, $this->sourcePath);
        $command[] = $this->buildDiskPath($this->targetDisk, $this->targetPath);

        return $command;
    }

    protected function buildEnvironment(): array
    {
        $environment = [];

        // Build environment variables for source disk
        if (isset($this->filesystemDisks[$this->sourceDisk])) {
            $config = $this->filesystemDisks[$this->sourceDisk];
            $environment = array_merge($environment, $this->buildDiskEnvironment($this->sourceDisk, $config));
        }

        // Build environment variables for target disk
        if (isset($this->filesystemDisks[$this->targetDisk])) {
            $config = $this->filesystemDisks[$this->targetDisk];
            $environment = array_merge($environment, $this->buildDiskEnvironment($this->targetDisk, $config));
        }

        return $environment;
    }

    protected function buildDiskEnvironment(string $diskName, array $config): array
    {
        $env = [];
        $upperDiskName = strtoupper($diskName);
        $driver = $config['driver'] ?? 'local';

        switch ($driver) {
            case 'local':
                $env["RCLONE_CONFIG_{$upperDiskName}_TYPE"] = 'local';
                $env["RCLONE_CONFIG_{$upperDiskName}_ROOT"] = $config['root'] ?? '/';
                break;

            case 's3':
                $env["RCLONE_CONFIG_{$upperDiskName}_TYPE"] = 's3';
                $env["RCLONE_CONFIG_{$upperDiskName}_ACCESS_KEY_ID"] = $config['key'] ?? '';
                $env["RCLONE_CONFIG_{$upperDiskName}_SECRET_ACCESS_KEY"] = $config['secret'] ?? '';
                $env["RCLONE_CONFIG_{$upperDiskName}_REGION"] = $config['region'] ?? 'us-east-1';
                $env["RCLONE_CONFIG_{$upperDiskName}_BUCKET"] = $config['bucket'] ?? '';

                if (isset($config['endpoint'])) {
                    $env["RCLONE_CONFIG_{$upperDiskName}_ENDPOINT"] = $config['endpoint'];
                }

                if (isset($config['use_path_style_endpoint'])) {
                    $env["RCLONE_CONFIG_{$upperDiskName}_FORCE_PATH_STYLE"] = $config['use_path_style_endpoint'] ? 'true' : 'false';
                }
                break;

            case 'sftp':
                $env["RCLONE_CONFIG_{$upperDiskName}_TYPE"] = 'sftp';
                $env["RCLONE_CONFIG_{$upperDiskName}_HOST"] = $config['host'] ?? '';
                $env["RCLONE_CONFIG_{$upperDiskName}_USER"] = $config['username'] ?? '';
                $env["RCLONE_CONFIG_{$upperDiskName}_PASS"] = $config['password'] ?? '';
                $env["RCLONE_CONFIG_{$upperDiskName}_PORT"] = (string) ($config['port'] ?? 22);
                break;

            case 'ftp':
                $env["RCLONE_CONFIG_{$upperDiskName}_TYPE"] = 'ftp';
                $env["RCLONE_CONFIG_{$upperDiskName}_HOST"] = $config['host'] ?? '';
                $env["RCLONE_CONFIG_{$upperDiskName}_USER"] = $config['username'] ?? '';
                $env["RCLONE_CONFIG_{$upperDiskName}_PASS"] = $config['password'] ?? '';
                $env["RCLONE_CONFIG_{$upperDiskName}_PORT"] = (string) ($config['port'] ?? 21);
                break;

            default:
                throw new InvalidArgumentException("Unsupported driver: {$driver}");
        }

        return $env;
    }

    protected function buildDiskPath(string $diskName, string $path): string
    {
        return $diskName.':'.ltrim($path, '/');
    }

    protected function normalizePath(string $path): string
    {
        return '/'.trim($path, '/');
    }

    protected function validateConfiguration(): void
    {
        if (empty($this->sourceDisk)) {
            throw new RuntimeException('Source disk is required');
        }

        if (empty($this->targetDisk)) {
            throw new RuntimeException('Target disk is required');
        }
    }

    protected function findRcloneBinary(): string
    {
        $paths = [
            '/usr/bin/rclone',
            '/usr/local/bin/rclone',
            '/bin/rclone',
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        // Try to find in PATH
        $which = shell_exec('which rclone 2>/dev/null');
        if ($which && trim($which)) {
            return trim($which);
        }

        throw new RuntimeException('rclone binary not found. Please install rclone or set binary_path in configuration.');
    }

    protected function parseStats(string $output): array
    {
        $stats = [
            'transferred_files' => 0,
            'transferred_bytes' => 0,
            'errors' => 0,
            'checks' => 0,
            'elapsed_time' => 0,
        ];

        // This is a simplified stats parser - you would implement more sophisticated parsing
        // based on rclone's output format similar to flyclone's parseFinalStats method

        return $stats;
    }
}
