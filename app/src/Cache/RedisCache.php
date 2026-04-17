<?php

declare(strict_types=1);

namespace Sintoniza\Cache;

use Monolog\Logger;
use Predis\ClientInterface;
use Predis\PredisException;

class RedisCache implements CacheInterface
{
    private bool $disabled = false;

    public function __construct(private ClientInterface $client, private Logger $logger) {}

    public function get(string $key): mixed
    {
        if ($this->disabled) {
            return null;
        }

        try {
            $raw = $this->client->get($key);
        } catch (PredisException $e) {
            $this->trip($e);
            return null;
        }

        if ($raw === null) {
            return null;
        }

        $value = @unserialize($raw, ['allowed_classes' => true]);
        return $value === false && $raw !== serialize(false) ? null : $value;
    }

    public function set(string $key, mixed $value, int $ttl): void
    {
        if ($this->disabled) {
            return;
        }

        try {
            if ($ttl > 0) {
                $this->client->setex($key, $ttl, serialize($value));
            } else {
                $this->client->set($key, serialize($value));
            }
        } catch (PredisException $e) {
            $this->trip($e);
        }
    }

    public function forget(string $key): void
    {
        if ($this->disabled) {
            return;
        }

        try {
            $this->client->del([$key]);
        } catch (PredisException $e) {
            $this->trip($e);
        }
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        if ($value !== null) {
            $this->set($key, $value, $ttl);
        }

        return $value;
    }

    private function trip(PredisException $e): void
    {
        $this->disabled = true;
        $this->logger->warning('Redis cache unavailable, falling back to direct execution', [
            'error' => $e->getMessage(),
        ]);
    }
}
