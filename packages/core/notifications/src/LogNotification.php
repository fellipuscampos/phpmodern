<?php

declare(strict_types=1);

namespace PhpModern\Notifications;

interface LogNotification extends Notification
{
    public function toLogMessage(): string;
}
