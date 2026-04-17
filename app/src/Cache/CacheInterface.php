<?php

declare(strict_types=1);

namespace Sintoniza\Cache;

interface CacheInterface
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, int $ttl): void;

    public function forget(string $key): void;

    public function remember(string $key, int $ttl, callable $callback): mixed;
}
