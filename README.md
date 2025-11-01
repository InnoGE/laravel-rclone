# Laravel Rclone

**A sleek Laravel package that wraps rclone with an elegant, fluent API syntax.**

[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://packagist.org/packages/innoge/laravel-rclone)
[![Laravel Version](https://img.shields.io/badge/laravel-10.x%7C11.x%7C12.x-orange)](https://packagist.org/packages/innoge/laravel-rclone)
[![Test Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen)](https://packagist.org/packages/innoge/laravel-rclone)
[![Total Downloads](https://img.shields.io/packagist/dt/innoge/laravel-rclone.svg?style=flat-square)](https://packagist.org/packages/innoge/laravel-rclone)

## ✨ Features

- 🎯 **Fluent API** - Laravel-style method chaining
- 🚀 **Driver Agnostic** - Local, S3, SFTP, FTP support
- ⚡ **Zero Configuration** - Uses Laravel filesystem config
- 🔧 **Highly Configurable** - Override any rclone option
- 📊 **Progress Tracking** - Real-time callbacks & stats
- 🧪 **100% Test Coverage** - Battle-tested with Pest
- 🏗️ **Laravel Native** - Service Provider, Facade, Auto-Discovery

## 🚀 Quick Start

**Install via Composer:**
```bash
composer require innoge/laravel-rclone
```

**Basic usage:**
```php
use InnoGE\LaravelRclone\Facades\Rclone;

// Simple sync
Rclone::source('s3', 'documents')
    ->target('backup', 'archive')
    ->sync();

// With progress & options
Rclone::source('s3', 'media/photos')
    ->target('local', 'storage/backups')
    ->withProgress()
    ->transfers(16)
    ->checkers(8)
    ->getOutputUsing(fn($type, $output) => Log::info("Rclone: {$output}"))
    ->sync();
```

## 📋 Requirements

- **PHP 8.2+**
- **Laravel 10.x, 11.x, or 12.x**
- **rclone binary** - [Installation Guide](https://rclone.org/install/)

## ⚙️ Configuration

The package automatically uses your existing `config/filesystems.php` configuration:

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
    'sftp' => [
        'driver' => 'sftp',
        'host' => env('SFTP_HOST'),
        'username' => env('SFTP_USERNAME'),
        'password' => env('SFTP_PASSWORD'),
        'port' => env('SFTP_PORT', 22),
    ],
]
```

**Optional: Publish config for advanced settings:**
```bash
php artisan vendor:publish --provider="InnoGE\LaravelRclone\RcloneServiceProvider" --tag="rclone-config"
```

## 🎯 API Reference

### Core Operations
```php
// Sync (make target identical to source)
$result = Rclone::source('s3', 'data')->target('backup')->sync();

// Copy (don't delete from target)
$result = Rclone::source('s3', 'data')->target('backup')->copy();

// Move (delete from source after transfer)
$result = Rclone::source('s3', 'data')->target('backup')->move();
```

### Performance Tuning
```php
Rclone::source('s3', 'large-dataset')
    ->target('backup', 'archive')
    ->transfers(32)          // Parallel transfers
    ->checkers(16)           // File checkers
    ->retries(5)             // Retry attempts
    ->statInterval(10)       // Stats interval (seconds)
    ->option('bandwidth', '50M')  // Custom rclone option
    ->sync();
```

### Progress Monitoring
```php
$result = Rclone::source('s3', 'files')
    ->target('local', 'backup')
    ->withProgress()
    ->getOutputUsing(function ($type, $output) {
        if ($type === 'out') {
            echo "Progress: {$output}";
        } else {
            Log::error("Rclone Error: {$output}");
        }
    })
    ->sync();

// Check results
if ($result->isSuccessful()) {
    $stats = $result->getStats();
    echo "Transferred: {$stats['transferred_files']} files\n";
    echo "Data: {$stats['transferred_bytes']} bytes\n";
} else {
    echo "Failed: {$result->getErrorOutput()}\n";
}
```

## 🔌 Supported Storage Providers

| Provider | Driver | Required Config |
|----------|--------|-----------------|
| **Local Filesystem** | `local` | `root` |
| **Amazon S3** | `s3` | `key`, `secret`, `region`, `bucket` |
| **SFTP** | `sftp` | `host`, `username`, `password`/`key_file` |
| **FTP** | `ftp` | `host`, `username`, `password` |

### S3 Configuration
```php
's3' => [
    'driver' => 's3',
    'key' => 'AKIAIOSFODNN7EXAMPLE',
    'secret' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'region' => 'us-west-2',
    'bucket' => 'my-bucket',
    'endpoint' => 'https://s3.amazonaws.com', // Optional
    'use_path_style_endpoint' => false,        // Optional
]
```

### SFTP Authentication
```php
'sftp' => [
    'driver' => 'sftp',
    'host' => 'server.example.com',
    'username' => 'user',
    // Password auth
    'password' => 'secret',
    // OR Key file auth
    'key_file' => '/path/to/private/key',
    // OR Inline key auth
    'private_key' => '-----BEGIN OPENSSH PRIVATE KEY-----...',
    'port' => 22,
]
```

## 🏗️ Laravel Integration

### Dependency Injection
```php
use InnoGE\LaravelRclone\Contracts\RcloneInterface;

class BackupService
{
    public function __construct(
        private RcloneInterface $rclone
    ) {}

    public function dailyBackup(): bool
    {
        $result = $this->rclone
            ->source('local', 'app/data')
            ->target('s3', 'backups/daily')
            ->withProgress()
            ->sync();

        return $result->isSuccessful();
    }
}
```

### Artisan Command Example
```php
use InnoGE\LaravelRclone\Facades\Rclone;

class BackupCommand extends Command
{
    public function handle(): int
    {
        $this->info('Starting backup...');

        $result = Rclone::source('local', 'storage/app')
            ->target('s3', 'backups/' . now()->format('Y-m-d'))
            ->withProgress()
            ->getOutputUsing(fn($type, $output) => $this->line($output))
            ->sync();

        return $result->isSuccessful() ? 0 : 1;
    }
}
```

## 🛠️ Advanced Configuration

**Environment variables:**
```env
RCLONE_BINARY_PATH=/usr/local/bin/rclone
RCLONE_TIMEOUT=7200
```

**Custom config file (`config/rclone.php`):**
```php
return [
    'binary_path' => env('RCLONE_BINARY_PATH', 'rclone'),
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
        'progress' => false,
    ],
];
```

## 🧪 Testing

```bash
# Run tests
composer test

# With coverage
composer test-coverage

# Static analysis
composer analyse

# Format code
composer format
```

## 🤝 Contributing

Contributions welcome! Please submit issues and pull requests on [GitHub](https://github.com/innoge/laravel-rclone).

## 💝 Built With Love

This package was crafted with passion by amazing developers:

- [Daniel Seuffer](https://github.com/authanram) - Creator & Maintainer
- [Tim Geisendoerfer](https://github.com/geisi) - Inspiring Leader, Core Contributor
- [All Contributors](../../contributors) - Community Heroes ❤️

## 📄 License

MIT License. See [LICENSE](LICENSE.md) for details.

---

⚡ **Powered by [rclone](https://rclone.org/)** - The Swiss Army knife of cloud storage
