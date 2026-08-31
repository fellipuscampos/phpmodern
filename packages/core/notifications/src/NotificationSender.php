<?php

declare(strict_types=1);

namespace PhpModern\Notifications;

use InvalidArgumentException;
use PhpModern\Logging\Logger;
use PhpModern\Logging\LogLevel;
use PhpModern\Mail\Mailer;

/**
 * One call sends a Notification through every channel it names in via() —
 * the call site doesn't reach for Mailer and Logger separately and doesn't
 * repeat the same message construction logic per channel.
 */
final class NotificationSender
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly Logger $logger,
    ) {
    }

    public function send(string $recipientEmail, Notification $notification): void
    {
        foreach ($notification->via() as $channel) {
            $this->sendVia($channel, $recipientEmail, $notification);
        }
    }

    private function sendVia(string $channel, string $recipientEmail, Notification $notification): void
    {
        match ($channel) {
            'mail' => $this->sendMail($recipientEmail, $notification),
            'log' => $this->sendLog($notification),
            default => throw new InvalidArgumentException("Unsupported notification channel: {$channel}"),
        };
    }

    private function sendMail(string $recipientEmail, Notification $notification): void
    {
        if (!$notification instanceof MailNotification) {
            throw new InvalidArgumentException(sprintf(
                '%s declares "mail" in via() but does not implement %s.',
                $notification::class,
                MailNotification::class,
            ));
        }

        $this->mailer->send($notification->toMail($recipientEmail));
    }

    private function sendLog(Notification $notification): void
    {
        if (!$notification instanceof LogNotification) {
            throw new InvalidArgumentException(sprintf(
                '%s declares "log" in via() but does not implement %s.',
                $notification::class,
                LogNotification::class,
            ));
        }

        $this->logger->log(LogLevel::Info, $notification->toLogMessage());
    }
}
