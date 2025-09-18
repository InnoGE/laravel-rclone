<?php

namespace InnoGE\LaravelRclone\Contracts;

use Closure;
use InnoGE\LaravelRclone\Support\ProcessResult;

interface RcloneInterface
{
    public function source(string $disk, string $path = '/'): self;

    public function target(string $disk, string $path = '/'): self;

    public function withProgress(bool $progress = true): self;

    public function transfers(int $transfers): self;

    public function checkers(int $checkers): self;

    public function retries(int $retries): self;

    public function statInterval(int $seconds): self;

    public function getOutputUsing(Closure $callback): self;

    public function option(string $key, mixed $value): self;

    public function sync(): ProcessResult;

    public function copy(): ProcessResult;

    public function move(): ProcessResult;
}
