<?php

declare(strict_types=1);

namespace Filagora\Core\Schema;

use InvalidArgumentException;

/**
 * Extra columns, indexes and foreign keys a profile package (for example one for a particular
 * site) adds to tables owned by another package. `filagora:adopt` checks the package's snapshot
 * plus every registered extension exactly, and still reports anything undeclared as unexpected.
 *
 * @phpstan-import-type Column from TableShape
 * @phpstan-import-type Index from TableShape
 * @phpstan-import-type ForeignKey from TableShape
 */
final readonly class SchemaExtension
{
    /**
     * @param  string  $name  Who declares it, for example `zencart`.
     * @param  string  $module  The package whose tables it extends, for example `forums`.
     * @param  array<string, array{columns?: array<string, Column>, indexes?: array<string, Index>, foreign_keys?: array<string, ForeignKey>}>  $tables  Keyed by base table name.
     * @param  string|null  $migrationsPath  Where the extension's migrations live, recorded as run on adoption.
     */
    public function __construct(
        public string $name,
        public string $module,
        public array $tables,
        public ?string $migrationsPath = null,
    ) {}

    /**
     * @param  string  $snapshotFile  A PHP file returning `['tables' => [base name => extras]]`, as `filagora:schema:dump-extension` writes it.
     */
    public static function fromFile(string $name, string $module, string $snapshotFile, ?string $migrationsPath = null): self
    {
        $data = require $snapshotFile;

        if (! is_array($data) || ! isset($data['tables']) || ! is_array($data['tables'])) {
            throw new InvalidArgumentException("Schema extension [{$snapshotFile}] must return an array with a 'tables' key.");
        }

        return new self($name, $module, $data['tables'], $migrationsPath);
    }

    /**
     * @return list<string>
     */
    public function migrationNames(): array
    {
        if ($this->migrationsPath === null) {
            return [];
        }

        $files = glob(rtrim($this->migrationsPath, '/').'/*.php') ?: [];
        sort($files);

        return array_map(fn (string $file): string => basename($file, '.php'), $files);
    }
}
