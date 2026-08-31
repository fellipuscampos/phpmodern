<?php

declare(strict_types=1);

namespace PhpModern\Notifications\Tests\Fixtures;

use PhpModern\Mail\Message;
use PhpModern\Notifications\LogNotification;
use PhpModern\Notifications\MailNotification;

final class WelcomeNotification implements MailNotification, LogNotification
{
    public function __construct(private readonly string $username)
    {
    }

    public function via(): array
    {
        return ['mail', 'log'];
    }

    public function toMail(string $recipientEmail): Message
    {
        return new Message(
            to: $recipientEmail,
            from: 'noreply@phpmodern.test',
            subject: 'Welcome!',
            textBody: "Welcome, {$this->username}!",
        );
    }

    public function toLogMessage(): string
    {
        return "Sent welcome notification to {$this->username}.";
    }
}
