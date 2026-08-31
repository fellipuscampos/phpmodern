<?php

declare(strict_types=1);

namespace PhpModern\Notifications;

/**
 * A notification declares which channels it supports via via() and
 * implements the matching per-channel interface (MailNotification,
 * LogNotification) for each one it names — NotificationSender checks both,
 * so a channel name with no matching implementation is a clear error
 * instead of a silently-skipped send.
 */
interface Notification
{
    /** @return list<string> e.g. ['mail', 'log'] */
    public function via(): array;
}
