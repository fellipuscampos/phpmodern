<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Console;

use InvalidArgumentException;
use RuntimeException;

/**
 * Scaffolds a new Model subclass — matching phpmodern/orm's deliberately
 * non-magic shape (readonly typed properties, no __get/__set attribute
 * bag): table()/id()/fromRow()/attributes() stubs with one example `name`
 * column, ready to rename or extend, the same "generate a small concrete
 * example, not an empty shell" choice MakeComponentCommand already made.
 */
final class MakeModelCommand
{
    /** @return string the path of the created file */
    public function run(string $rawName, string $targetDir, string $baseNamespace): string
    {
        $relative = str_replace('\\', '/', trim($rawName, '/\\'));
        $segments = array_values(array_filter(explode('/', $relative), static fn (string $s): bool => $s !== ''));

        if ($segments === []) {
            throw new InvalidArgumentException("Invalid model name: {$rawName}");
        }

        $className = self::studly((string) array_pop($segments));

        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $className) !== 1) {
            throw new InvalidArgumentException("Invalid model name: {$rawName}");
        }

        $subNamespace = implode('\\', array_map([self::class, 'studly'], $segments));
        $namespace = $subNamespace === '' ? $baseNamespace : "{$baseNamespace}\\{$subNamespace}";

        $subDir = implode('/', $segments);
        $dir = $subDir === '' ? $targetDir : "{$targetDir}/{$subDir}";
        $path = "{$dir}/{$className}.php";

        if (is_file($path)) {
            throw new RuntimeException("Model already exists: {$path}");
        }

        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException("Could not create directory: {$dir}");
        }

        file_put_contents($path, $this->generateSource($className, $namespace));

        return $path;
    }

    public function generateSource(string $className, string $namespace): string
    {
        $table = self::pluralize(self::snake($className));

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        use PhpModern\Orm\Model;

        final class {$className} extends Model
        {
            public function __construct(
                public readonly ?int \$id,
                public readonly string \$name,
            ) {
            }

            public static function table(): string
            {
                return '{$table}';
            }

            public function id(): ?int
            {
                return \$this->id;
            }

            public static function fromRow(array \$row): self
            {
                return new self((int) \$row['id'], (string) \$row['name']);
            }

            public function attributes(): array
            {
                return ['name' => \$this->name];
            }
        }

        PHP;
    }

    private static function studly(string $value): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9]+/', ' ', $value) ?? '';

        return str_replace(' ', '', ucwords(trim($normalized)));
    }

    private static function snake(string $value): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }

    private static function pluralize(string $word): string
    {
        if (preg_match('/(s|x|z|ch|sh)$/', $word) === 1) {
            return $word . 'es';
        }

        if (preg_match('/[^aeiou]y$/', $word) === 1) {
            return substr($word, 0, -1) . 'ies';
        }

        return $word . 's';
    }
}
