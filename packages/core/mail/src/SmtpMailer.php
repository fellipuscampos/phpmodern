<?php

declare(strict_types=1);

namespace PhpModern\Mail;

use RuntimeException;

/**
 * A minimal SMTP client over a plain TCP socket — no external library.
 * Speaks EHLO, optional AUTH LOGIN, MAIL FROM/RCPT TO/DATA, QUIT. No
 * STARTTLS, no MIME multipart/attachments: meant for a local relay (e.g.
 * MailHog, or Python's aiosmtpd, used to verify this class for real) or an
 * internal mail server already trusted on the network — not for talking
 * directly to a public mailbox provider, which requires TLS.
 */
final class SmtpMailer implements Mailer
{
    public function __construct(
        private readonly string $host,
        private readonly int $port = 25,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
    ) {
    }

    public function send(Message $message): void
    {
        $socket = @stream_socket_client("tcp://{$this->host}:{$this->port}", $errno, $errstr, 10);

        if ($socket === false) {
            throw new RuntimeException("Could not connect to SMTP server {$this->host}:{$this->port}: {$errstr}");
        }

        try {
            self::expect($socket, 220);
            self::command($socket, "EHLO phpmodern\r\n", 250);

            if ($this->username !== null && $this->password !== null) {
                self::command($socket, "AUTH LOGIN\r\n", 334);
                self::command($socket, base64_encode($this->username) . "\r\n", 334);
                self::command($socket, base64_encode($this->password) . "\r\n", 235);
            }

            self::command($socket, "MAIL FROM:<{$message->from}>\r\n", 250);
            self::command($socket, "RCPT TO:<{$message->to}>\r\n", 250);
            self::command($socket, "DATA\r\n", 354);
            self::command($socket, self::buildDataSection($message), 250);
            self::command($socket, "QUIT\r\n", 221);
        } finally {
            fclose($socket);
        }
    }

    /** The DATA section (headers + body + terminating "."), pure and testable without a socket. */
    public static function buildDataSection(Message $message): string
    {
        $headers = [
            "From: {$message->from}",
            "To: {$message->to}",
            "Subject: {$message->subject}",
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];

        // RFC 5321 dot-stuffing: a body line that starts with "." must be escaped to "..".
        $body = preg_replace('/^\./m', '..', $message->textBody);

        return implode("\r\n", $headers) . "\r\n\r\n{$body}\r\n.\r\n";
    }

    /** @param resource $socket */
    private static function expect($socket, int $expectedCode): string
    {
        $response = '';

        do {
            $line = fgets($socket, 512);

            if ($line === false) {
                throw new RuntimeException('SMTP connection closed unexpectedly.');
            }

            $response .= $line;
        } while (isset($line[3]) && $line[3] === '-'); // a multi-line reply continues as "250-..."

        $code = (int) substr($response, 0, 3);

        if ($code !== $expectedCode) {
            throw new RuntimeException("Unexpected SMTP response (expected {$expectedCode}): {$response}");
        }

        return $response;
    }

    /** @param resource $socket */
    private static function command($socket, string $command, int $expectedCode): string
    {
        fwrite($socket, $command);

        return self::expect($socket, $expectedCode);
    }
}
