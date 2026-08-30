<?php

declare(strict_types=1);

namespace PhpModern\Http;

final class Request
{
    /**
     * @param array<string, string> $query
     * @param array<string, string> $headers lowercase header name => value
     */
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        private readonly string $rawBody,
        private readonly array $headers,
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');

        /** @var array<string, string> $query */
        $query = $_GET;

        return new self(
            $method,
            $path,
            $query,
            (string) (file_get_contents('php://input') ?: ''),
            self::extractHeaders($_SERVER),
        );
    }

    /**
     * Builds a Request without touching superglobals — the way tests (and
     * anything simulating a request in-process) construct one.
     *
     * @param array<string, string> $query
     * @param array<string, string> $headers
     */
    public static function create(
        string $method,
        string $path,
        array $query = [],
        string $rawBody = '',
        array $headers = [],
    ): self {
        $normalizedHeaders = [];

        foreach ($headers as $name => $value) {
            $normalizedHeaders[strtolower($name)] = $value;
        }

        return new self(strtoupper($method), $path, $query, $rawBody, $normalizedHeaders);
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function rawBody(): string
    {
        return $this->rawBody;
    }

    /** @return array<string, mixed>|null null if the body is empty or not a JSON object/array */
    public function json(): ?array
    {
        if ($this->rawBody === '') {
            return null;
        }

        $decoded = json_decode($this->rawBody, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<array-key, mixed> $server
     * @return array<string, string>
     */
    private static function extractHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (is_string($key) && is_string($value) && str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        if (isset($server['CONTENT_TYPE']) && is_string($server['CONTENT_TYPE'])) {
            $headers['content-type'] = $server['CONTENT_TYPE'];
        }

        return $headers;
    }
}
