# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### Testing
```bash
composer test           # Run all tests with Pest
composer test-coverage  # Run tests with coverage report

# Run specific test file
vendor/bin/pest tests/Feature/LaravelIntegrationTest.php
# Run specific test method
vendor/bin/pest --filter="can access rclone via service container"
```

### Code Quality
```bash
composer analyse        # Run PHPStan static analysis
composer format         # Format code with Laravel Pint
```

### Development
```bash
# Install dependencies
composer install

# Publish config to test package integration
php artisan vendor:publish --provider="InnoGE\LaravelRclone\RcloneServiceProvider" --tag="rclone-config"
```

## Coding Standards

### PHP Standards
- **PHP Version**: 8.2+
- **Type Declarations**: Always use explicit return type declarations for methods and functions
- **Constructor**: Use PHP 8 constructor property promotion
- **Control Structures**: Always use curly braces for control structures, even single lines
- **Comments**: Prefer PHPDoc blocks over inline comments

```php
// ✅ Good - Constructor property promotion with return types
public function __construct(
    private array $config,
    private array $filesystemDisks
) {}

public function source(string $disk, string $path = '/'): self
{
    // Implementation
}

// ❌ Bad - No return type, no property promotion
public function __construct($config, $filesystemDisks)
{
    $this->config = $config;
}

public function source($disk, $path = '/')
{
    // Implementation
}
```

### Laravel Package Conventions
- Use `php artisan make:` commands when available for consistency
- Follow Laravel's naming conventions (StudlyCase for classes, camelCase for methods/variables)
- Use fluent method chaining for builder patterns
- Implement contracts/interfaces for better testability

### Code Quality Tools
- **PHPStan**: Level 8 static analysis - run `composer analyse` before commits
- **Laravel Pint**: PSR-12 code formatting - run `composer format` to fix styling
- **Pest Testing**: Feature and unit tests - run `composer test` to verify changes

### Testing Best Practices
- Use Pest PHP testing framework
- Write feature tests for public API behavior
- Use Orchestra Testbench for Laravel package testing
- Mock external dependencies (rclone binary execution)
- Test error conditions and edge cases
- Use descriptive test names that explain the behavior being tested

```php
// ✅ Good test structure
it('throws exception when source disk is not set', function () {
    $manager = app(RcloneInterface::class);
    $manager->target('local_test', '/backup');

    expect(fn () => $manager->sync())
        ->toThrow(RuntimeException::class, 'Source disk is required');
});
```

## Architecture

### Package Structure
This is a Laravel package (`innoge/laravel-rclone`) that wraps the rclone command-line tool with a fluent API. The package uses Laravel's service container, facades, and auto-discovery.

**Core Components:**
- `RcloneServiceProvider` - Registers the service and publishes config
- `RcloneInterface` - Contract defining the fluent API
- `RcloneManager` - Main implementation that builds rclone commands and executes them
- `Rclone` Facade - Provides static access to the fluent API
- `ProcessResult` - Value object containing command execution results

### Key Design Patterns

**Fluent Builder Pattern:**
The API chains configuration methods that return `$this`, ending with execution methods (`sync()`, `copy()`, `move()`) that return `ProcessResult`.

**Laravel Filesystem Integration:**
The package reads disk configurations from `config/filesystems.php` and automatically converts them to rclone environment variables. Supported drivers: `local`, `s3`, `sftp`, `ftp`.

**Environment Variable Configuration:**
Rclone is configured via environment variables (e.g., `RCLONE_CONFIG_S3_TYPE=s3`) rather than config files, allowing for dynamic configuration per Laravel disk.

### Critical Implementation Details

**Command Execution:**
- Uses Laravel's `Process` facade for command execution
- Builds rclone commands as arrays to prevent shell injection
- Environment variables are generated per disk and merged for source/target
- Progress tracking via output callbacks during process execution

**Disk Path Resolution:**
- Converts Laravel disk names to rclone remote format (`disk:path`)
- Normalizes paths to prevent issues with leading/trailing slashes
- Validates required source and target disks before execution

**Testing:**
- Uses Orchestra Testbench for Laravel package testing
- Test configurations in `tests/TestCase.php` set up mock disks (`s3_test`, `local_test`, `sftp_test`)
- Tests use `--dry-run` option to avoid actual rclone operations

### Namespace
All classes use the `InnoGE\LaravelRclone\` namespace (recently migrated from `InnoGE\Rclone\`).