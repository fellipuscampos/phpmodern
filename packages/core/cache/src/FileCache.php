<?php

declare(strict_types=1);

namespace PhpModern\Cache;

/**
 * One JSON file per key, named by a hash of the key so arbitrary key
 * strings never have to survive as filesystem-safe paths. get()/has() do a
 * plain read; increment() holds an exclusive flock() across its whole
 * read-modify-write so two requests incrementing the same counter at once
 * can't both read the same starting value and silently lose one.
 */
final class FileCache implements Cache
{
    public function __construct(private readonly string $directory)
    {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }
    }

    public function get(string $key): mixed
    {
        return $this->read($key)['value'] ?? null;
    }

    public function has(string $key): bool
    {
        return $this->read($key) !== null;
    }

    public function set(string $key, mixed $value, int $ttlSeconds): void
    {
        file_put_contents(
            $this->pathFor($key),
            json_encode(['value' => $value, 'expires_at' => time() + $ttlSeconds], JSON_THROW_ON_ERROR),
            LOCK_EX,
        );
    }

    public function delete(string $key): void
    {
        @unlink($this->pathFor($key));
    }

    public function increment(string $key, int $ttlSeconds): int
    {
        $handle = fopen($this->pathFor($key), 'c+');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open cache file for key: {$key}");
        }

        flock($handle, LOCK_EX);

        $raw = stream_get_contents($handle);
        $decoded = $raw === false || $raw === '' ? null : json_decode($raw, true);
        $now = time();

        if (is_array($decoded) && isset($decoded['expires_at'], $decoded['value']) && (int) $decoded['expires_at'] >= $now) {
            $value = (int) $decoded['value'] + 1;
            $expiresAt = (int) $decoded['expires_at'];
        } else {
            $value = 1;
            $expiresAt = $now + $ttlSeconds;
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode(['value' => $value, 'expires_at' => $expiresAt], JSON_THROW_ON_ERROR));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return $value;
    }

    /**
     * @return array{value: mixed, expires_at: int}|null
     */
    private function read(string $key): ?array
    {
        $path = $this->pathFor($key);

        if (!is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (!is_array($decoded) || !isset($decoded['expires_at'], $decoded['value'])) {
            return null;
        }

        if ((int) $decoded['expires_at'] < time()) {
            @unlink($path);

            return null;
        }

        return ['value' => $decoded['value'], 'expires_at' => (int) $decoded['expires_at']];
    }

    private function pathFor(string $key): string
    {
        return $this->directory . '/' . hash('sha256', $key) . '.json';
    }
}
