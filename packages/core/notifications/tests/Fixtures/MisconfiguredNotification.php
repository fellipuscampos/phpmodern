<?php

declare(strict_types=1);

namespace PhpModern\Notifications\Tests\Fixtures;

use PhpModern\Notifications\Notification;

/**
 * Declares "mail" in via() without implementing MailNotification — proves
 * NotificationSender catches this misconfiguration instead of silently
 * skipping the channel.
 */
final class MisconfiguredNotification implements Notification
{
    public function via(): array
    {
        return ['mail'];
    }
}
