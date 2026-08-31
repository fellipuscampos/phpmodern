<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Console;

use InvalidArgumentException;
use RuntimeException;

/**
 * Scaffolds a new Job — a plain class implementing phpmodern/queue's Job
 * interface, ready to push via DatabaseQueue/RedisQueue::push(). Generates
 * a constructor-property-per-payload-key shape, since that's exactly what
 * Worker::runOnce() hydrates a job from (`new $jobClass(...$payload)`).
 */
final class MakeJobCommand
{
    /** @return string the path of the created file */
    public function run(string $rawName, string $targetDir, string $baseNamespace): string
    {
        $relative = str_replace('\\', '/', trim($rawName, '/\\'));
        $segments = array_values(array_filter(explode('/', $relative), static fn (string $s): bool => $s !== ''));

        if ($segments === []) {
            throw new InvalidArgumentException("Invalid job name: {$rawName}");
        }

        $className = self::studly((string) array_pop($segments));

        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $className) !== 1) {
            throw new InvalidArgumentException("Invalid job name: {$rawName}");
        }

        $subNamespace = implode('\\', array_map([self::class, 'studly'], $segments));
        $namespace = $subNamespace === '' ? $baseNamespace : "{$baseNamespace}\\{$subNamespace}";

        $subDir = implode('/', $segments);
        $dir = $subDir === '' ? $targetDir : "{$targetDir}/{$subDir}";
        $path = "{$dir}/{$className}.php";

        if (is_file($path)) {
            throw new RuntimeException("Job already exists: {$path}");
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

        use PhpModern\Queue\Job;

        final class {$className} implements Job
        {
            public function __construct(
                public readonly int \$id,
            ) {
            }

            public function handle(): void
            {
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
