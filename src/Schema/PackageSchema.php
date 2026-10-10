<?php

declare(strict_types=1);

namespace ForumKit\Core\Schema;

use InvalidArgumentException;

/**
 * The tables a package owns, as they exist in a site that already uses them, plus where
 * the package's migrations live. `forumkit:adopt` checks a database against this and, if
 * it matches, records the migrations as run.
 *
 * The snapshot is the state after all of the package's migrations, so it changes whenever
 * a migration adds or alters a column.
 */
final readonly class PackageSchema
{
    /**
     * @param  array<string, TableShape>  $tables  Keyed by base table name (no prefix).
     * @param  list<string>  $extraMigrations  Migration names contributed by schema extensions.
     */
    public function __construct(
        public string $module,
        public array $tables,
        public string $migrationsPath,
        public array $extraMigrations = [],
    ) {}

    /**
     * @param  string  $snapshotFile  A PHP file returning `['tables' => [base name => shape array]]`.
     */
    public static function fromFile(string $module, string $snapshotFile, string $migrationsPath): self
    {
        $data = require $snapshotFile;

        if (! is_array($data) || ! isset($data['tables']) || ! is_array($data['tables'])) {
            throw new InvalidArgumentException("Schema snapshot [{$snapshotFile}] must return an array with a 'tables' key.");
        }

        $tables = [];

        foreach ($data['tables'] as $name => $shape) {
            $tables[(string) $name] = TableShape::fromArray($shape);
        }

        return new self($module, $tables, $migrationsPath);
    }

    /**
     * Migration names as the migrations table records them (file name without `.php`).
     *
     * @return list<string>
     */
    public function migrationNames(): array
    {
        $files = glob(rtrim($this->migrationsPath, '/').'/*.php') ?: [];
        sort($files);

        return array_values(array_unique([
            ...array_map(fn (string $file): string => basename($file, '.php'), $files),
            ...$this->extraMigrations,
        ]));
    }

    /**
     * This schema with extensions applied: their extras added to the tables they name, and their
     * migrations counted as the package's own.
     *
     * @param  list<SchemaExtension>  $extensions
     */
    public function extendedBy(array $extensions): self
    {
        $tables = $this->tables;
        $migrations = $this->extraMigrations;

        foreach ($extensions as $extension) {
            foreach ($extension->tables as $base => $extras) {
                if (! isset($tables[$base])) {
                    throw new InvalidArgumentException("Schema extension [{$extension->name}] extends table [{$base}], which module [{$this->module}] does not own.");
                }

                $tables[$base] = $tables[$base]->withExtras($extras);
            }

            $migrations = [...$migrations, ...$extension->migrationNames()];
        }

        return new self($this->module, $tables, $this->migrationsPath, array_values(array_unique($migrations)));
    }
}
