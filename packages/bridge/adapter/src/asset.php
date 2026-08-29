<?php

declare(strict_types=1);

namespace PhpModern\Bridge;

/**
 * Appends a `?v=<mtime>` query string to an asset URL, keyed off the source
 * file's last-modified time. Lets the response be cached aggressively
 * (`Cache-Control: public, max-age=31536000, immutable`) while still
 * guaranteeing a fresh fetch the moment the underlying file changes — the
 * same idea as a content hash in a bundler-generated filename, without
 * requiring a build step.
 */
function versioned_asset_url(string $url, string $absoluteFilePath): string
{
    $mtime = @filemtime($absoluteFilePath);

    if ($mtime === false) {
        return $url;
    }

    $separator = str_contains($url, '?') ? '&' : '?';

    return "{$url}{$separator}v={$mtime}";
}
