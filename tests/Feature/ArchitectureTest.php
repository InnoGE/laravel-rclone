<?php

declare(strict_types=1);

use InnoGE\LaravelRclone\Exceptions\RcloneException;

arch('app')->expect(['dump', 'dd', 'ray'])->not->toBeUsed();
arch()->preset()->php();
arch()->preset()->security()->ignoring(['shell_exec']);

// Exception Architecture Tests
test('all exceptions should extend RcloneException')
    ->expect('InnoGE\LaravelRclone\Exceptions')
    ->toExtend(RcloneException::class)
    ->ignoring(RcloneException::class);

test('exception classes should be suffixed with Exception')
    ->expect('InnoGE\LaravelRclone\Exceptions')
    ->toHaveSuffix('Exception');

test('all PHP files should not use declare strict_types per coding standards')
    ->expect('InnoGE\LaravelRclone')
    ->not->toUse('declare(strict_types=1)');
