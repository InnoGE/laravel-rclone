<?php

declare(strict_types=1);

namespace InnoGE\LaravelRclone\Support;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use InnoGE\LaravelRclone\Contracts\RcloneInterface;
use InnoGE\LaravelRclone\Exceptions\CommandExecutionException;
use InnoGE\LaravelRclone\Exceptions\InvalidConfigurationException;
use InnoGE\LaravelRclone\Exceptions\ProviderNotFoundException;
use Psr\Log\LoggerInterface;

class RcloneManager implements RcloneInterface
{
    protected array $config;

    protected array $filesystemDisks;

    protected ProviderRegistry $providerRegistry;

    protected LoggerInterface $logger;

    protected string $sourceDisk = '';

    protected string $sourcePath = '/';

    protected string $targetDisk = '';

    protected string $targetPath = '/';

    protected array $options = [];

    protected ?Closure $outputCallback = null;

    public function __construct(
        array $config = [],
        array $filesystemDisks = [],
        ?ProviderRegistry $providerRegistry = null,
        ?LoggerInterface $logger = null
    ) {
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
        $this->providerRegistry = $providerRegistry ?? new ProviderRegistry;
        $this->logger = $logger ?? Log::getFacadeRoot();
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

    /**
     * @throws InvalidConfigurationException
     */
    public function transfers(int $transfers): self
    {
        if ($transfers < 1 || $transfers > 100) {
            throw InvalidConfigurationException::invalidValue(
                'transfers',
                $transfers,
                'integer between 1 and 100'
            );
        }

        $this->options['transfers'] = $transfers;

        return $this;
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function checkers(int $checkers): self
    {
        if ($checkers < 1 || $checkers > 100) {
            throw InvalidConfigurationException::invalidValue(
                'checkers',
                $checkers,
                'integer between 1 and 100'
            );
        }

        $this->options['checkers'] = $checkers;

        return $this;
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function retries(int $retries): self
    {
        if ($retries < 0 || $retries > 10) {
            throw InvalidConfigurationException::invalidValue(
                'retries',
                $retries,
                'integer between 0 and 10'
            );
        }

        $this->options['retries'] = $retries;

        return $this;
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function statInterval(int $seconds): self
    {
        if ($seconds < 1 || $seconds > 3600) {
            throw InvalidConfigurationException::invalidValue(
                'stat_interval',
                $seconds,
                'integer between 1 and 3600 seconds'
            );
        }

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

    /**
     * @throws InvalidConfigurationException
     * @throws CommandExecutionException
     * @throws ProviderNotFoundException
     */
    public function sync(): ProcessResult
    {
        return $this->executeCommand('sync');
    }

    /**
     * @throws InvalidConfigurationException
     * @throws CommandExecutionException
     * @throws ProviderNotFoundException
     */
    public function copy(): ProcessResult
    {
        return $this->executeCommand('copy');
    }

    /**
     * @throws CommandExecutionException
     * @throws ProviderNotFoundException
     * @throws InvalidConfigurationException
     */
    public function move(): ProcessResult
    {
        return $this->executeCommand('move');
    }

    /**
     * @throws InvalidConfigurationException
     * @throws CommandExecutionException
     * @throws ProviderNotFoundException
     */
    protected function executeCommand(string $operation): ProcessResult
    {
        $this->validateConfiguration();

        $command = $this->buildCommand($operation);
        $environment = $this->buildEnvironment();

        $startTime = microtime(true);

        $this->logger->info('Starting rclone operation', [
            'operation' => $operation,
            'source' => $this->sourceDisk.':'.$this->sourcePath,
            'target' => $this->targetDisk.':'.$this->targetPath,
            'timeout' => $this->config['timeout'],
        ]);

        $process = Process::timeout($this->config['timeout'])
            ->env($environment)
            ->run($command, function (string $type, string $buffer) {
                // @codeCoverageIgnoreStart
                if ($this->outputCallback) {
                    ($this->outputCallback)($type, $buffer);
                }
                // Log process output for debugging
                $this->logger->debug('rclone process output', [
                    'type' => $type,
                    'buffer' => Str::trim($buffer),
                ]);
                // @codeCoverageIgnoreEnd
            });

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        $result = new ProcessResult(
            $process->successful(),
            $process->exitCode(),
            $process->output(),
            $process->errorOutput(),
            $this->parseStats($process->errorOutput())
        );

        if ($result->isSuccessful()) {
            $this->logger->info('rclone operation completed successfully', [
                'operation' => $operation,
                'execution_time_ms' => $executionTime,
                'stats' => $result->getStats(),
            ]);
        } else {
            $this->logger->error('rclone operation failed', [
                'operation' => $operation,
                'exit_code' => $result->getExitCode(),
                'execution_time_ms' => $executionTime,
                'error_output' => $result->getErrorOutput(),
            ]);
        }

        return $result;
    }

    /**
     * @throws CommandExecutionException
     * @throws InvalidConfigurationException
     */
    protected function buildCommand(string $operation): array
    {
        $binary = $this->config['binary_path'] ?? $this->findRcloneBinary();

        $builder = RcloneCommandBuilder::create($binary, $operation)
            ->addBaseOptions($this->config['base_options']);

        // Add dynamic options
        foreach ($this->options as $key => $value) {
            $builder->addOption($key, $value);
        }

        // Add source and target
        $source = $this->buildDiskPath($this->sourceDisk, $this->sourcePath);
        $target = $this->buildDiskPath($this->targetDisk, $this->targetPath);

        $builder->addSourceAndTarget($source, $target);

        $command = $builder->build();

        $this->logger->debug('Built rclone command', [
            'operation' => $operation,
            'command' => $command,
            'source' => $source,
            'target' => $target,
        ]);

        return $command;
    }

    /**
     * @throws ProviderNotFoundException
     */
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

    /**
     * @throws ProviderNotFoundException
     */
    protected function buildDiskEnvironment(string $diskName, array $config): array
    {
        $driver = $config['driver'] ?? 'local';
        $provider = $this->providerRegistry->getProvider($driver);

        return $provider->buildEnvironment($diskName, $config);
    }

    protected function buildDiskPath(string $diskName, string $path): string
    {
        return $diskName.':'.Str::ltrim($path, '/');
    }

    protected function normalizePath(string $path): string
    {
        return '/'.Str::trim($path, '/');
    }

    /**
     * @throws CommandExecutionException
     */
    protected function validateConfiguration(): void
    {
        if (empty($this->sourceDisk)) {
            throw CommandExecutionException::missingDisk('Source');
        }

        if (empty($this->targetDisk)) {
            throw CommandExecutionException::missingDisk('Target');
        }
    }

    /**
     * @throws CommandExecutionException
     */
    protected function findRcloneBinary(): string
    {
        $paths = [
            '/bin/rclone',
            '/usr/bin/rclone',
            '/usr/local/bin/rclone',
            '/opt/homebrew/bin/rclone',
        ];

        // @codeCoverageIgnoreStart
        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        // Try to find in PATH
        $which = shell_exec('which rclone 2>/dev/null');
        if ($which && Str::trim($which)) {
            return Str::trim($which);
        }
        // @codeCoverageIgnoreEnd

        throw CommandExecutionException::binaryNotFound(); // @codeCoverageIgnore
    }

    protected function parseStats(string $output): array
    {
        return StatsParser::parse($output);
    }
}
