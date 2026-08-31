<?php

declare(strict_types=1);

namespace PhpModern\Kernel\Console;

use InvalidArgumentException;
use RuntimeException;

/**
 * Scaffolds a new migration file — a timestamp-prefixed filename (so
 * MigrationRunner's lexical `sort($names)` is also chronological order,
 * matching its own discovery convention) containing a `return`ed anonymous
 * class implementing Migration, exactly what MigrationRunner::load()
 * expects, no separate registry or autoload wiring needed. A name matching
 * `create_X_table` gets a real CREATE/DROP TABLE stub instead of an empty
 * one — cheap to detect, and it's the single most common migration shape.
 */
final class MakeMigrationCommand
{
    /** @return string the path of the created file */
    public function run(string $rawName, string $migrationsDir): string
    {
        $name = self::snake($rawName);

        if ($name === '' || preg_match('/^[a-z][a-z0-9_]*$/', $name) !== 1) {
            throw new InvalidArgumentException("Invalid migration name: {$rawName}");
        }

        $path = "{$migrationsDir}/" . date('Y_m_d_His') . "_{$name}.php";

        if (is_file($path)) {
            throw new RuntimeException("Migration already exists: {$path}");
        }

        if (!is_dir($migrationsDir) && !mkdir($migrationsDir, 0777, true) && !is_dir($migrationsDir)) {
            throw new RuntimeException("Could not create directory: {$migrationsDir}");
        }

        file_put_contents($path, $this->generateSource($name));

        return $path;
    }

    public function generateSource(string $name): string
    {
        if (preg_match('/^create_(.+)_table$/', $name, $matches) === 1) {
            $table = $matches[1];

            return <<<PHP
            <?php

            declare(strict_types=1);

            use PhpModern\Orm\Connection;
            use PhpModern\Orm\Migration;

            return new class implements Migration {
                public function up(Connection \$connection): void
                {
                    \$connection->pdo()->exec('CREATE TABLE {$table} (
                        id INTEGER PRIMARY KEY,
                        created_at TEXT,
                        updated_at TEXT
                    )');
                }

                public function down(Connection \$connection): void
                {
                    \$connection->pdo()->exec('DROP TABLE {$table}');
                }
            };

            PHP;
        }

        return <<<PHP
        <?php

        declare(strict_types=1);

        use PhpModern\Orm\Connection;
        use PhpModern\Orm\Migration;

        return new class implements Migration {
            public function up(Connection \$connection): void
            {
            }

            public function down(Connection \$connection): void
            {
            }
        };

        PHP;
    }

    private static function snake(string $value): string
    {
        $value = (string) preg_replace('/(?<!^)[A-Z]/', '_$0', trim($value));
        $value = (string) preg_replace('/[^A-Za-z0-9]+/', '_', $value);

        return strtolower(trim($value, '_'));
    }
}
