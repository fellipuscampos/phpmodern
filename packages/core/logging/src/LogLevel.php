<?php

declare(strict_types=1);

namespace PhpModern\Logging;

/**
 * A typed alternative to PSR-3's string-constant severity levels — a caller
 * can pass a level that doesn't exist with `\Psr\Log\LogLevel::WARNING`
 * (it's just a string), but not with this enum.
 */
enum LogLevel: string
{
    case Debug = 'debug';
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';
    case Critical = 'critical';
}
