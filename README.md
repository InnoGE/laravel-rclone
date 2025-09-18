# Laravel Rclone

A sleek Laravel package that wraps rclone with an elegant, fluent API syntax.

## Features

- 🎯 **Laravel-style fluent API** - Beautiful, readable syntax
- 🚀 **Driver agnostic** - Supports Local, S3, SFTP, FTP and more
- ⚡ **Zero configuration** - Works out of the box with Laravel
- 🔧 **Highly configurable** - Override any rclone option
- 📊 **Progress tracking** - Real-time output callbacks
- 🧪 **Fully tested** - Comprehensive test coverage with Orchestra Testbench
- 🏗️ **Laravel Integration** - Service Provider, Facade, and Auto-Discovery
- 🎨 **Laravel Pint** - Consistent code styling

## Requirements

- PHP 8.2 or higher
- Laravel 10.x, 11.x, or 12.x
- rclone binary installed on the system

## Installation

Install the package via Composer:

```bash
composer require innoge/laravel-rclone
```

The package will automatically register itself via Laravel's auto-discovery.

Make sure you have [rclone](https://rclone.org/install/) installed on your system.

### Publish Configuration (Optional)

You can publish the configuration file to customize default settings:

```bash
php artisan vendor:publish --provider="InnoGE\Rclone\RcloneServiceProvider" --tag="rclone-config"
```

This will create a `config/rclone.php` file where you can set your preferences.

## Quick Start

The package automatically integrates with your Laravel filesystem configuration. Just define your disks in `config/filesystems.php` as usual:

```php
// config/filesystems.php
'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'bucket' => env('AWS_BUCKET'),
    ],
    'backup' => [
        'driver' => 's3',
        'key' => env('BACKUP_ACCESS_KEY'),
        'secret' => env('BACKUP_SECRET_KEY'),
        'region' => env('BACKUP_REGION', 'eu-west-1'),
        'bucket' => env('BACKUP_BUCKET'),
    ],
    'sftp' => [
        'driver' => 'sftp',
        'host' => env('SFTP_HOST'),
        'username' => env('SFTP_USERNAME'),
        'password' => env('SFTP_PASSWORD'),
        'port' => env('SFTP_PORT', 22),
    ],
],
```

### Usage

Use the Facade for elegant syntax:

```php
use InnoGE\LaravelRclone\Facades\Rclone;

// Basic sync
Rclone::source('s3', 'documents/important')
    ->target('backup', 'backups/documents')
    ->sync();

// With progress tracking and custom options
Rclone::source('s3', 'media/photos')
    ->target('local', 'storage/backups/photos')
    ->withProgress()
    ->transfers(16)
    ->checkers(8)
    ->retries(3)
    ->statInterval(5)
    ->getOutputUsing(function ($type, $output) {
        Log::info("Rclone: {$output}");
    })
    ->sync();

// Or use dependency injection
class BackupService
{
    public function __construct(
        private RcloneInterface $rclone
    ) {}

    public function backupToS3(): ProcessResult
    {
        return $this->rclone
            ->source('local', 'app/backups')
            ->target('s3', 'daily-backups')
            ->withProgress()
            ->sync();
    }
}
```

## API Reference

### Facade Methods

```php
use InnoGE\LaravelRclone\Facades\Rclone;

// Source/target pattern
Rclone::source('s3', 'path/to/source');
Rclone::target('backup', 'path/to/target');

// Method chaining
Rclone::source('s3', 'documents')
    ->target('backup', 'archived-documents')
    ->withProgress()
    ->sync();
```

### Fluent Configuration

```php
Rclone::source('s3', 'data')
    ->target('backup', 'archive')
    ->withProgress(true)           // Show progress
    ->transfers(32)                // Parallel transfers
    ->checkers(16)                 // File checkers
    ->retries(5)                   // Retry attempts
    ->statInterval(10)             // Stats interval in seconds
    ->getOutputUsing($callback)    // Output callback
    ->option('bandwidth', '10M')   // Custom rclone option
    ->sync();                      // Execute sync
```

### Operations

```php
// Sync (make identical)
$result = Rclone::source('s3')->target('local')->sync();

// Copy (don't delete from target)
$result = Rclone::source('s3')->target('local')->copy();

// Move (delete from source)
$result = Rclone::source('s3')->target('local')->move();
```

### Configuration

You can set environment variables in your `.env` file:

```env
RCLONE_BINARY_PATH=/usr/local/bin/rclone
RCLONE_TIMEOUT=7200
```

Or publish and customize the configuration file:

```php
// config/rclone.php
return [
    'binary_path' => env('RCLONE_BINARY_PATH'),
    'timeout' => env('RCLONE_TIMEOUT', 3600),
    'base_options' => [
        '--delete-after',
        '--fast-list',
        '--checksum',
    ],
    'defaults' => [
        'transfers' => 4,
        'checkers' => 8,
        'retries' => 3,
        'stat_interval' => 1,
        'progress' => false,
    ],
];
```

## Supported Drivers

### Local Filesystem

```php
'local' => [
    'driver' => 'local',
    'root' => '/path/to/directory',
]
```

### Amazon S3

```php
's3' => [
    'driver' => 's3',
    'key' => 'your-access-key-id',
    'secret' => 'your-secret-access-key',
    'region' => 'us-east-1',
    'bucket' => 'your-bucket-name',
    'endpoint' => 'https://s3.amazonaws.com', // optional
    'use_path_style_endpoint' => false,       // optional
]
```

### SFTP

```php
'sftp' => [
    'driver' => 'sftp',
    'host' => 'your-server.com',
    'username' => 'username',
    'password' => 'password',
    'port' => 22,
]
```

### FTP

```php
'ftp' => [
    'driver' => 'ftp',
    'host' => 'ftp.example.com',
    'username' => 'username',
    'password' => 'password',
    'port' => 21,
]
```

## Usage Examples

### Laravel Integration

If you're using Laravel, you can integrate with the filesystem configuration:

```php
// In a Service Provider
use InnoGE\LaravelRclone\Rclone;

public function boot()
{
    Rclone::setFilesystemConfiguration(config('filesystems.disks'));
}

// In your application
Rclone::source('s3', 'uploads')
    ->target('backup', 'archived-uploads')
    ->withProgress()
    ->sync();
```

### Progress Tracking

```php
$transferredFiles = 0;
$errors = [];

$result = Rclone::source('s3', 'large-dataset')
    ->target('local', 'backup/dataset')
    ->withProgress()
    ->getOutputUsing(function ($type, $buffer) use (&$transferredFiles, &$errors) {
        if ($type === 'out') {
            echo "Progress: $buffer";
            // Parse progress information
        } else {
            $errors[] = $buffer;
        }
    })
    ->sync();

if ($result->isSuccessful()) {
    echo "Sync completed successfully!\n";
    echo "Transferred files: " . $result->getTransferredFiles() . "\n";
    echo "Transferred bytes: " . $result->getTransferredBytes() . "\n";
} else {
    echo "Sync failed with exit code: " . $result->getExitCode() . "\n";
    echo "Error: " . $result->getErrorOutput() . "\n";
}
```

### Custom Configuration

```php
// Override default rclone options
Rclone::configure([
    'base_options' => [
        '--delete-after',
        '--fast-list',
        '--checksum',
        '--verbose',
    ],
    'defaults' => [
        'transfers' => 16,
        'checkers' => 32,
        'retries' => 5,
    ],
]);
```

## Error Handling

The package returns a `ProcessResult` object with detailed information:

```php
$result = Rclone::source('s3')->target('local')->sync();

if ($result->failed()) {
    echo "Exit code: " . $result->getExitCode() . "\n";
    echo "Error output: " . $result->getErrorOutput() . "\n";
    echo "Standard output: " . $result->getOutput() . "\n";
} else {
    $stats = $result->getStats();
    echo "Success! Transferred {$stats['transferred_files']} files\n";
}
```

## Requirements

- PHP 8.1 or higher
- rclone binary installed on the system
- Symfony Process component

## Testing

Run the test suite:

```bash
composer test
```

## License

MIT License. See [LICENSE](LICENSE.md) for more information.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
