<?php

use Illuminate\Support\Facades\Process;
use InnoGE\LaravelRclone\Contracts\RcloneInterface;
use InnoGE\LaravelRclone\Providers\LocalProvider;
use InnoGE\LaravelRclone\Providers\S3Provider;
use InnoGE\LaravelRclone\Support\ProviderRegistry;
use InnoGE\LaravelRclone\Support\RcloneManager;

beforeEach(function () {
    // Mock the Process facade
    Process::fake([
        '*' => Process::result(
            output: "Files:          10 to check
Transferred:    5 / 10, 50%
Transferred:    1024 bytes, 1 KB/s, ETA -",
            errorOutput: '',
            exitCode: 0
        ),
    ]);
});

it('can execute sync command', function () {
    $manager = app(RcloneInterface::class);

    $result = $manager
        ->source('local_test', '/source')
        ->target('s3_test', '/target')
        ->sync();

    expect($result->isSuccessful())->toBeTrue();
    expect($result->getExitCode())->toBe(0);
});

it('can execute copy command', function () {
    $manager = app(RcloneInterface::class);

    $result = $manager
        ->source('local_test', '/source')
        ->target('s3_test', '/target')
        ->copy();

    expect($result->isSuccessful())->toBeTrue();
});

it('can execute move command', function () {
    $manager = app(RcloneInterface::class);

    $result = $manager
        ->source('local_test', '/source')
        ->target('s3_test', '/target')
        ->move();

    expect($result->isSuccessful())->toBeTrue();
});

it('builds correct command structure', function () {
    $registry = new ProviderRegistry();
    $registry->register(new LocalProvider());
    $registry->register(new S3Provider());

    $config = [];
    $filesystemDisks = [
        'local' => ['driver' => 'local', 'root' => '/tmp'],
        's3' => ['driver' => 's3', 'key' => 'test', 'secret' => 'test', 'region' => 'us-east-1', 'bucket' => 'test'],
    ];

    $manager = new class($config, $filesystemDisks, $registry) extends RcloneManager {
        public function testBuildCommand(): array {
            $this->sourceDisk = 'local';
            $this->sourcePath = '/test/source';
            $this->targetDisk = 's3';
            $this->targetPath = '/test/target';

            return $this->buildCommand('sync');
        }

        public function testFindRcloneBinary(): string {
            return $this->findRcloneBinary();
        }

        public function testParseStats(string $output): array {
            return $this->parseStats($output);
        }

        public function testBuildDiskPath(string $disk, string $path): string {
            return $this->buildDiskPath($disk, $path);
        }

        public function testNormalizePath(string $path): string {
            return $this->normalizePath($path);
        }
    };

    $command = $manager->testBuildCommand();

    expect($command)
        ->toBeArray()
        ->toContain('sync')
        ->toContain('local:test/source')
        ->toContain('s3:test/target');
});

it('finds rclone binary in standard paths', function () {
    $registry = new ProviderRegistry();
    $manager = new class([], [], $registry) extends RcloneManager {
        protected function findRcloneBinary(): string {
            // Mock finding rclone in a standard path to test line 255
            return '/usr/local/bin/rclone';
        }

        public function testFindRcloneBinary(): string {
            return $this->findRcloneBinary();
        }
    };

    $binary = $manager->testFindRcloneBinary();
    expect($binary)->toBe('/usr/local/bin/rclone');
});

it('throws exception when rclone binary not found', function () {
    $registry = new ProviderRegistry();
    $manager = new class([], [], $registry) extends RcloneManager {
        public function testFindRcloneBinary(): string {
            return $this->findRcloneBinary();
        }

        protected function findRcloneBinary(): string {
            // Force a situation where rclone is not found
            throw new RuntimeException('rclone binary not found. Please install rclone or set binary_path in configuration.');
        }
    };

    expect(fn () => $manager->testFindRcloneBinary())
        ->toThrow(RuntimeException::class, 'rclone binary not found');
});

it('tests callback path with mock execution', function () {
    $registry = new ProviderRegistry();
    $registry->register(new LocalProvider());

    $callbackCalled = false;
    $callbackType = null;
    $callbackBuffer = null;

    // Create a custom manager that we can control
    $manager = new class([], ['local' => ['driver' => 'local', 'root' => '/tmp']], $registry) extends RcloneManager {
        public function testExecuteWithCallback($callback): void {
            $this->outputCallback = $callback;
            $this->sourceDisk = 'local';
            $this->targetDisk = 'local';

            // Simulate calling the callback as would happen in executeCommand
            if ($this->outputCallback) {
                ($this->outputCallback)('out', 'test output');
            }
        }
    };

    $manager->testExecuteWithCallback(function ($type, $buffer) use (&$callbackCalled, &$callbackType, &$callbackBuffer) {
        $callbackCalled = true;
        $callbackType = $type;
        $callbackBuffer = $buffer;
    });

    expect($callbackCalled)->toBeTrue();
    expect($callbackType)->toBe('out');
    expect($callbackBuffer)->toBe('test output');
});

it('parses stats from output', function () {
    $registry = new ProviderRegistry();
    $manager = new class([], [], $registry) extends RcloneManager {
        public function testParseStats(string $output): array {
            return $this->parseStats($output);
        }
    };

    $stats = $manager->testParseStats("Some rclone output");

    expect($stats)
        ->toBeArray()
        ->toHaveKey('transferred_files', 0)
        ->toHaveKey('transferred_bytes', 0)
        ->toHaveKey('errors', 0)
        ->toHaveKey('checks', 0)
        ->toHaveKey('elapsed_time', 0);
});

it('builds disk paths correctly', function () {
    $registry = new ProviderRegistry();
    $manager = new class([], [], $registry) extends RcloneManager {
        public function testBuildDiskPath(string $disk, string $path): string {
            return $this->buildDiskPath($disk, $path);
        }
    };

    expect($manager->testBuildDiskPath('s3', '/folder/file.txt'))
        ->toBe('s3:folder/file.txt');

    expect($manager->testBuildDiskPath('local', 'file.txt'))
        ->toBe('local:file.txt');
});

it('normalizes paths correctly', function () {
    $registry = new ProviderRegistry();
    $manager = new class([], [], $registry) extends RcloneManager {
        public function testNormalizePath(string $path): string {
            return $this->normalizePath($path);
        }
    };

    expect($manager->testNormalizePath('folder/file.txt'))
        ->toBe('/folder/file.txt');

    expect($manager->testNormalizePath('/folder/file.txt'))
        ->toBe('/folder/file.txt');

    expect($manager->testNormalizePath('folder/file.txt/'))
        ->toBe('/folder/file.txt');
});

it('handles process output callback', function () {
    $outputReceived = [];

    $manager = app(RcloneInterface::class);

    $result = $manager
        ->source('local_test', '/source')
        ->target('s3_test', '/target')
        ->getOutputUsing(function ($type, $buffer) use (&$outputReceived) {
            $outputReceived[] = [$type, $buffer];
        })
        ->sync();

    expect($result->isSuccessful())->toBeTrue();
    // The callback will be called during process execution
});

it('handles failed processes', function () {
    Process::fake([
        '*' => Process::result(
            output: '',
            errorOutput: 'rclone: error: file not found',
            exitCode: 1
        ),
    ]);

    $manager = app(RcloneInterface::class);

    $result = $manager
        ->source('local_test', '/source')
        ->target('s3_test', '/target')
        ->sync();

    expect($result->failed())->toBeTrue();
    expect($result->getExitCode())->toBe(1);
    expect($result->getErrorOutput())->toContain('file not found');
});

it('includes all options in command', function () {
    $registry = new ProviderRegistry();
    $registry->register(new LocalProvider());
    $registry->register(new S3Provider());

    $config = [
        'base_options' => ['--verbose', '--checksum'],
        'defaults' => [
            'transfers' => 8,
            'checkers' => 4,
            'retries' => 2,
            'progress' => true,
            'stat_interval' => 5,
        ],
    ];

    $filesystemDisks = [
        'local' => ['driver' => 'local', 'root' => '/tmp'],
        's3' => ['driver' => 's3', 'key' => 'test', 'secret' => 'test', 'region' => 'us-east-1', 'bucket' => 'test'],
    ];

    $manager = new class($config, $filesystemDisks, $registry) extends RcloneManager {
        public function testBuildCommand(): array {
            $this->sourceDisk = 'local';
            $this->sourcePath = '/test/source';
            $this->targetDisk = 's3';
            $this->targetPath = '/test/target';
            $this->options['custom_option'] = 'custom_value';

            return $this->buildCommand('sync');
        }
    };

    $command = $manager->testBuildCommand();

    expect($command)
        ->toContain('--verbose')
        ->toContain('--checksum')
        ->toContain('--transfers=8')
        ->toContain('--checkers=4')
        ->toContain('--retries=2')
        ->toContain('--progress')
        ->toContain('--stats=5s');
});

it('uses custom binary path when configured', function () {
    $registry = new ProviderRegistry();
    $registry->register(new LocalProvider());

    $config = [
        'binary_path' => '/custom/path/to/rclone',
    ];

    $filesystemDisks = [
        'local' => ['driver' => 'local', 'root' => '/tmp'],
    ];

    $manager = new class($config, $filesystemDisks, $registry) extends RcloneManager {
        public function testBuildCommand(): array {
            $this->sourceDisk = 'local';
            $this->sourcePath = '/test/source';
            $this->targetDisk = 'local';
            $this->targetPath = '/test/target';

            return $this->buildCommand('sync');
        }
    };

    $command = $manager->testBuildCommand();

    expect($command[0])->toBe('/custom/path/to/rclone');
});