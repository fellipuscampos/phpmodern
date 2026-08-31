<?php

declare(strict_types=1);

namespace PhpModern\Notifications\Tests;

use InvalidArgumentException;
use PhpModern\Logging\FileLogger;
use PhpModern\Mail\LogMailer;
use PhpModern\Notifications\NotificationSender;
use PhpModern\Notifications\Tests\Fixtures\LogOnlyNotification;
use PhpModern\Notifications\Tests\Fixtures\MisconfiguredNotification;
use PhpModern\Notifications\Tests\Fixtures\WelcomeNotification;
use PHPUnit\Framework\TestCase;

final class NotificationSenderTest extends TestCase
{
    private string $mailPath;
    private string $logPath;
    private NotificationSender $sender;

    protected function setUp(): void
    {
        $unique = uniqid('', true);
        $this->mailPath = sys_get_temp_dir() . "/phpmodern-notifications-mail-{$unique}.log";
        $this->logPath = sys_get_temp_dir() . "/phpmodern-notifications-app-{$unique}.log";

        $this->sender = new NotificationSender(new LogMailer($this->mailPath), new FileLogger($this->logPath));
    }

    protected function tearDown(): void
    {
        @unlink($this->mailPath);
        @unlink($this->logPath);
    }

    public function test_send_delivers_through_every_channel_the_notification_names(): void
    {
        $this->sender->send('user@example.test', new WelcomeNotification('Ada'));

        $mailLog = (string) file_get_contents($this->mailPath);
        self::assertStringContainsString('user@example.test', $mailLog);
        self::assertStringContainsString('Welcome, Ada!', $mailLog);

        $appLog = (string) file_get_contents($this->logPath);
        self::assertStringContainsString('Sent welcome notification to Ada.', $appLog);
    }

    public function test_a_notification_using_only_one_channel_does_not_touch_the_other(): void
    {
        $this->sender->send('user@example.test', new LogOnlyNotification());

        self::assertFileDoesNotExist($this->mailPath);
        self::assertStringContainsString('log only', (string) file_get_contents($this->logPath));
    }

    public function test_declaring_a_channel_without_implementing_it_throws_a_clear_error(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/MailNotification/');

        $this->sender->send('user@example.test', new MisconfiguredNotification());
    }
}
