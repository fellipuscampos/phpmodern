<?php

declare(strict_types=1);

namespace PhpModern\Notifications;

use PhpModern\Mail\Message;

interface MailNotification extends Notification
{
    public function toMail(string $recipientEmail): Message;
}
