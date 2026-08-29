<?php

declare(strict_types=1);

namespace PhpModern\Kernel;

final class Kernel
{
    public function __construct(private readonly Router $router)
    {
    }

    public function handle(string $method, string $path): string
    {
        $handler = $this->router->match($method, $path);

        if ($handler === null) {
            http_response_code(404);

            return '404 Not Found';
        }

        return $handler();
    }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

        echo $this->handle($method, $path);
    }
}
