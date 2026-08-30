<?php

declare(strict_types=1);

namespace PhpModern\Console;

/**
 * Parses a flat argv-style list into positional arguments and `--key=value`
 * / `--flag` options once, so a Command gets typed accessors instead of
 * grepping the raw array by hand.
 */
final class Input
{
    /** @var list<string> */
    private readonly array $arguments;

    /** @var array<string, string|true> */
    private readonly array $options;

    /**
     * @param list<string> $args everything after the command name
     */
    public function __construct(array $args)
    {
        $arguments = [];
        $options = [];

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $body = substr($arg, 2);

                if (str_contains($body, '=')) {
                    [$key, $value] = explode('=', $body, 2);
                    $options[$key] = $value;
                } else {
                    $options[$body] = true;
                }

                continue;
            }

            $arguments[] = $arg;
        }

        $this->arguments = $arguments;
        $this->options = $options;
    }

    public function argument(int $position): ?string
    {
        return $this->arguments[$position] ?? null;
    }

    /** @return list<string> */
    public function arguments(): array
    {
        return $this->arguments;
    }

    public function option(string $name, ?string $default = null): ?string
    {
        $value = $this->options[$name] ?? null;

        return is_string($value) ? $value : $default;
    }

    public function flag(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }
}
