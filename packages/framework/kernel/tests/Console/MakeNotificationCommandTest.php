<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Tests\Console;

use InvalidArgumentException;
use PhpModern\Kernel\Console\MakeNotificationCommand;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MakeNotificationCommandTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/phpmodern-make-notification-test-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        self::removeDir($this->tmpDir);
    }

    public function test_generates_a_mail_notification(): void
    {
        $path = (new MakeNotificationCommand())->run('WelcomeEmail', $this->tmpDir, 'App\\Notifications');

        self::assertFileExists($path);

        $source = (string) file_get_contents($path);
        self::assertStringContainsString('namespace App\Notifications;', $source);
        self::assertStringContainsString('final class WelcomeEmail implements MailNotification', $source);
        self::assertStringContainsString('public function toMail(string $recipientEmail): Message', $source);
        self::assertStringContainsString("return ['mail'];", $source);
    }

    public function test_refuses_to_overwrite_an_existing_notification(): void
    {
        $command = new MakeNotificationCommand();
        $command->run('Duplicate', $this->tmpDir, 'App\\Notifications');

        $this->expectException(RuntimeException::class);
        $command->run('Duplicate', $this->tmpDir, 'App\\Notifications');
    }

    public function test_rejects_a_name_that_cannot_become_a_valid_class(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MakeNotificationCommand())->run('123 bad name!', $this->tmpDir, 'App\\Notifications');
    }

    private static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = "{$dir}/{$entry}";
            is_dir($path) ? self::removeDir($path) : unlink($path);
        }

        rmdir($dir);
    }
}
