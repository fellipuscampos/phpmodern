<?php

declare(strict_types=1);

namespace PhpModern\Mail;

/**
 * Plain-text only in this version — no HTML body, no attachments, no MIME
 * multipart. Everything that needs those (a nicer-looking password reset
 * email) can wait; a working plain-text send is the prerequisite this
 * pillar was blocked on.
 */
final class Message
{
    public function __construct(
        public readonly string $to,
        public readonly string $from,
        public readonly string $subject,
        public readonly string $textBody,
    ) {
    }
}
