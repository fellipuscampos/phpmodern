<?php

declare(strict_types=1);

namespace PhpModern\Mail;

/**
 * Writes the message to a file instead of actually sending it — the same
 * idea as Laravel's "log" mail driver: see what would have gone out without
 * needing real SMTP credentials or risking emailing someone by accident.
 * The sensible default for local development.
 */
final class LogMailer implements Mailer
{
    public function __construct(private readonly string $logPath)
    {
    }

    public function send(Message $message): void
    {
        $entry = sprintf(
            "[%s] To: %s\nFrom: %s\nSubject: %s\n\n%s\n%s\n",
            date('Y-m-d H:i:s'),
            $message->to,
            $message->from,
            $message->subject,
            $message->textBody,
            str_repeat('-', 40),
        );

        file_put_contents($this->logPath, $entry, FILE_APPEND);
    }
}
