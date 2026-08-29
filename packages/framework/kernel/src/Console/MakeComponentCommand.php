<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Console;

use InvalidArgumentException;
use RuntimeException;

/**
 * Scaffolds a new Component subclass — the `make:component` half of the
 * `check` + `make` CLI pair. Generation is kept separate from filesystem
 * side effects (run() vs generateSource()) so the template is unit-testable
 * without touching disk.
 */
final class MakeComponentCommand
{
    /**
     * @return string the path of the created file
     */
    public function run(string $rawName, string $targetDir, string $baseNamespace): string
    {
        $relative = str_replace('\\', '/', trim($rawName, '/\\'));
        $segments = array_values(array_filter(explode('/', $relative), static fn (string $s): bool => $s !== ''));

        if ($segments === []) {
            throw new InvalidArgumentException("Invalid component name: {$rawName}");
        }

        $className = self::studly((string) array_pop($segments));

        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $className) !== 1) {
            throw new InvalidArgumentException("Invalid component name: {$rawName}");
        }

        $subNamespace = implode('\\', array_map([self::class, 'studly'], $segments));
        $namespace = $subNamespace === '' ? $baseNamespace : "{$baseNamespace}\\{$subNamespace}";

        $subDir = implode('/', $segments);
        $dir = $subDir === '' ? $targetDir : "{$targetDir}/{$subDir}";
        $path = "{$dir}/{$className}.php";

        if (is_file($path)) {
            throw new RuntimeException("Component already exists: {$path}");
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

        use PhpModern\ComponentEngine\Component;

        final class {$className} extends Component
        {
            public function __construct(
                string \$id,
                public readonly string \$title,
            ) {
                parent::__construct(\$id);
            }

            public function render(): string
            {
                return sprintf(
                    '<div id="%s">%s</div>',
                    htmlspecialchars(\$this->id, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars(\$this->title, ENT_QUOTES, 'UTF-8'),
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
