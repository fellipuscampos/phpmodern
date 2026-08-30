<?php

declare(strict_types=1);

namespace PhpModern\Mail\Tests;

use PhpModern\Mail\LogMailer;
use PhpModern\Mail\Message;
use PHPUnit\Framework\TestCase;

final class LogMailerTest extends TestCase
{
    private string $logPath;

    protected function setUp(): void
    {
        $this->logPath = sys_get_temp_dir() . '/phpmodern-mail-test-' . uniqid() . '.log';
    }

    protected function tearDown(): void
    {
        if (is_file($this->logPath)) {
            unlink($this->logPath);
        }
    }

    public function test_send_writes_the_message_to_the_log_file(): void
    {
        $mailer = new LogMailer($this->logPath);

        $mailer->send(new Message(
            to: 'user@example.com',
            from: 'noreply@phpmodern.test',
            subject: 'Reset your password',
            textBody: 'Click here to reset your password.',
        ));

        $contents = file_get_contents($this->logPath);

        self::assertStringContainsString('To: user@example.com', $contents);
        self::assertStringContainsString('From: noreply@phpmodern.test', $contents);
        self::assertStringContainsString('Subject: Reset your password', $contents);
        self::assertStringContainsString('Click here to reset your password.', $contents);
    }

    public function test_send_appends_rather_than_overwriting(): void
    {
        $mailer = new LogMailer($this->logPath);

        $mailer->send(new Message('a@example.com', 'from@example.com', 'First', 'one'));
        $mailer->send(new Message('b@example.com', 'from@example.com', 'Second', 'two'));

        $contents = file_get_contents($this->logPath);

        self::assertStringContainsString('a@example.com', $contents);
        self::assertStringContainsString('b@example.com', $contents);
    }
}
