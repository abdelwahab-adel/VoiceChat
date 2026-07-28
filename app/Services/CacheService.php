<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * File-based cache service.
 * 
 * Lightweight PSR-16-style cache.
 * Swap with Redis/Memcached by implementing the same interface.
 */
final class CacheService
{
    private string $path;

    public function __construct(string $path, private readonly int $defaultTtl = 3600)
    {
        $this->path = rtrim($path, '/');
        if (!is_dir($this->path)) @mkdir($this->path, 0755, true);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->file($key);
        if (!is_file($file)) return $default;
        $data = @unserialize((string) file_get_contents($file));
        if (!$data || !is_array($data) || !isset($data['expires'], $data['value'])) return $default;
        if ($data['expires'] < time()) {
            @unlink($file);
            return $default;
        }
        return $data['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $file = $this->file($key);
        $dir  = dirname($file);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $payload = serialize([
            'value'   => $value,
            'expires' => time() + ($ttl ?? $this->defaultTtl),
        ]);
        return (bool) @file_put_contents($file, $payload, LOCK_EX);
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): bool
    {
        $file = $this->file($key);
        return is_file($file) ? @unlink($file) : true;
    }

    public function flush(): bool
    {
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iter as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        return true;
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $value = $this->get($key);
        if ($value !== null) return $value;
        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    private function file(string $key): string
    {
        $hash = hash('sha256', $key);
        return $this->path . '/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2) . '/' . $hash . '.cache';
    }
}
