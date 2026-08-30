<?php

declare(strict_types=1);

namespace PhpModern\Mail\Tests;

use PhpModern\Mail\Message;
use PhpModern\Mail\SmtpMailer;
use PHPUnit\Framework\TestCase;

/**
 * buildDataSection() is the pure half of SmtpMailer, testable without a
 * live socket. The full send() flow (EHLO/AUTH/MAIL FROM/RCPT TO/DATA) was
 * verified for real against a live SMTP server (Python's aiosmtpd) rather
 * than mocked here — a mock would only prove this class agrees with
 * itself, not that it speaks real SMTP.
 */
final class SmtpMailerTest extends TestCase
{
    public function test_data_section_includes_headers_and_body(): void
    {
        $data = SmtpMailer::buildDataSection(new Message(
            to: 'user@example.com',
            from: 'noreply@phpmodern.test',
            subject: 'Reset your password',
            textBody: 'Click here to reset it.',
        ));

        self::assertStringContainsString("To: user@example.com\r\n", $data);
        self::assertStringContainsString("From: noreply@phpmodern.test\r\n", $data);
        self::assertStringContainsString("Subject: Reset your password\r\n", $data);
        self::assertStringContainsString('Click here to reset it.', $data);
    }

    public function test_data_section_ends_with_a_lone_dot_on_its_own_line(): void
    {
        $data = SmtpMailer::buildDataSection(new Message('a@example.com', 'b@example.com', 'S', 'body'));

        self::assertStringEndsWith("\r\n.\r\n", $data);
    }

    public function test_a_body_line_starting_with_a_dot_is_dot_stuffed(): void
    {
        $data = SmtpMailer::buildDataSection(new Message(
            'a@example.com',
            'b@example.com',
            'S',
            "line one\n.line two",
        ));

        self::assertStringContainsString("line one\n..line two", $data);
    }
}
