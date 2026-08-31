<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Console;

use InvalidArgumentException;
use RuntimeException;

/**
 * Scaffolds a new Notification implementing MailNotification — the most
 * common channel, and the one with a concrete return type (Message) to
 * fill in, unlike LogNotification's bare string. via()/toMail() are the
 * exact pair NotificationSender::send() looks for.
 */
final class MakeNotificationCommand
{
    /** @return string the path of the created file */
    public function run(string $rawName, string $targetDir, string $baseNamespace): string
    {
        $relative = str_replace('\\', '/', trim($rawName, '/\\'));
        $segments = array_values(array_filter(explode('/', $relative), static fn (string $s): bool => $s !== ''));

        if ($segments === []) {
            throw new InvalidArgumentException("Invalid notification name: {$rawName}");
        }

        $className = self::studly((string) array_pop($segments));

        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $className) !== 1) {
            throw new InvalidArgumentException("Invalid notification name: {$rawName}");
        }

        $subNamespace = implode('\\', array_map([self::class, 'studly'], $segments));
        $namespace = $subNamespace === '' ? $baseNamespace : "{$baseNamespace}\\{$subNamespace}";

        $subDir = implode('/', $segments);
        $dir = $subDir === '' ? $targetDir : "{$targetDir}/{$subDir}";
        $path = "{$dir}/{$className}.php";

        if (is_file($path)) {
            throw new RuntimeException("Notification already exists: {$path}");
        }

        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException("Could not create directory: {$dir}");
        }

        file_put_contents($path, $this->generateSource($className, $namespace));

        return $path;
    }

    public function generateSource(string $className, string $namespace): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        use PhpModern\Mail\Message;
        use PhpModern\Notifications\MailNotification;

        final class {$className} implements MailNotification
        {
            public function via(): array
            {
                return ['mail'];
            }

            public function toMail(string \$recipientEmail): Message
            {
                return new Message(
                    to: \$recipientEmail,
                    from: 'noreply@example.test',
                    subject: '',
                    textBody: '',
                );
            }
        }

        PHP;
    }

    private static function studly(string $value): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9]+/', ' ', $value) ?? '';

        return str_replace(' ', '', ucwords(trim($normalized)));
    }
}
