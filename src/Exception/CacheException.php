<?php

namespace Phoxy\Exception;

final class CacheException extends \Exception
{
    public const OPERATION_READ = 'read';
    public const OPERATION_WRITE = 'write';
    public const OPERATION_DELETE = 'delete';
    public const OPERATION_CLEAR = 'clear';
    public const OPERATION_CONNECT = 'connect';

    private string $operation;
    private string $cacheKey;
    private string $adapterName;

    public function __construct(
        string $message,
        string $operation = self::OPERATION_READ,
        string $cacheKey = '',
        string $adapterName = '',
        int $code = 0,
        \Throwable $previous = null
    ) {
        $this->operation = $operation;
        $this->cacheKey = $cacheKey;
        $this->adapterName = $adapterName;

        $fullMessage = $this->formatMessage($message);

        parent::__construct($fullMessage, $code, $previous);
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function getCacheKey(): string
    {
        return $this->cacheKey;
    }

    public function getAdapterName(): string
    {
        return $this->adapterName;
    }

    public static function connectionFailed(
        string $adapterName,
        string $details,
        \Throwable $previous = null
    ): static
    {
        return new static(
            "Failed to connect to cache: {$details}",
            self::OPERATION_CONNECT,
            '',
            $adapterName,
            500,
            $previous
        );
    }

    public static function readFailed(
        string $adapterName,
        string $cacheKey,
        string $details = '',
        \Throwable $previous = null
    ): static
    {
        return new static(
            "Failed to read from cache" . ($details ? ": {$details}" : ""),
            self::OPERATION_READ,
            $cacheKey,
            $adapterName,
            500,
            $previous
        );
    }

    public static function writeFailed(
        string $adapterName,
        string $cacheKey,
        string $details = '',
        \Throwable $previous = null
    ): static
    {
        return new static(
            "Failed to write to cache" . ($details ? ": {$details}" : ""),
            self::OPERATION_WRITE,
            $cacheKey,
            $adapterName,
            500,
            $previous
        );
    }

    public static function outOfMemory(
        string $adapterName,
        string $cacheKey = '',
        \Throwable $previous = null
    ): static
    {
        return new static(
            "Cache storage is full or out of memory",
            self::OPERATION_WRITE,
            $cacheKey,
            $adapterName,
            507, // Insufficient storage
            $previous
        );
    }

    public static function itemTooLarge(
        string $adapterName,
        string $cacheKey,
        int $size,
        int $maxSize,
        \Throwable $previous = null
    ): static
    {
        $message = sprintf("Cache item too large: %d bytes (max: %d bytes)", $size, $maxSize);

        return new static($message, self::OPERATION_WRITE, $cacheKey, $adapterName, 413, $previous);
    }

    public static function curruptedData(
        string $adapterName,
        string $cacheKey,
        \Throwable $previous = null
    ): static
    {

    }

    private function formatMessage(string $message): string
    {
        $parts = ["Cache Error"];

        if ($this->adapterName) {
            $parts[] = "[Adapter: {$this->adapterName}]";
        }

        if ($this->operation) {
            $parts[] = "[Operation: {$this->operation}]";
        }

        if ($this->cacheKey) {
            $parts[] = "[Key: {$this->cacheKey}]";
        }

        $parts[] = $message;

        return implode(' ', $parts);
    }
}