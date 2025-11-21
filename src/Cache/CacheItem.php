<?php

namespace Phoxy\Cache;

use Psr\Cache\CacheItemInterface;

final class CacheItem implements CacheItemInterface
{
    public function __construct(
        private string $key,
        private $value = null,
        private bool $isHit = false,
        private ?int $expiration = null
    ) {
        $this->key = $key;
        $this->value = $value;
        $this->isHit = $isHit;
        $this->expiration = $expiration;
    }

    /**
     * {@inheritDoc}
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * {@inheritDoc}
     */
    public function get(): mixed
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function isHit(): bool
    {
        return $this->isHit;
    }

    /**
     * {@inheritDoc}
     */
    public function set(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        ($expiration === null)
            ? $this->expiration = null
            : $this->expiration = $expiration->getTimestamp();

        return $this;
    }

    /**
     * {@inheritDoc}
     * 
     * @throws InvalidArgumentException
     */
    public function expiresAfter(int|\DateInterval|null $time): static
    {
        if ($time === null) {
            $this->expiration = null;
        } elseif (is_int($time)) {
            $this->expiration = time() + $time;
        } elseif ($time instanceof \DateInterval) {
            $this->expiration = (new \DateTime())->add($time)->getTimestamp();
        } else {
            throw new \InvalidArgumentException("Time must be integer, Date Interval or null");
        }

        return $this;
    }

    public function getExpirationTimestamp(): ?int
    {
        return $this->expiration;
    }

    /**
     * Check if the item has expired
     */
    public function isExpired(): bool
    {
        return ($this->expiration === null)
            ? false
            : time() > $this->expiration;
    }

    /**
     * Get time until expiration in seconds
     */
    public function getTtl(): ?int
    {
        if ($this->expiration === null) {
            return null;
        }

        $ttl = $this->expiration - time();

        return $ttl > 0 ? $ttl : 0;
    }

    /**
     * Create a hit cache item
     */
    public static function hit(string $key, $value, ?int $expiration = null): static
    {
        return new static($key, $value, true, $expiration);
    }

    /**
     * Create a miss cache item
     */
    public static function miss(string $key): static
    {
        return new static($key);
    }
}