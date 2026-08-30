<?php

declare(strict_types=1);

namespace PhpModern\Mail;

interface Mailer
{
    public function send(Message $message): void;
}
