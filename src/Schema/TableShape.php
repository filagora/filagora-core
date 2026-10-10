<?php

declare(strict_types=1);

namespace ForumKit\Core\Schema;

use ForumKit\Core\Support\Tables;
use ForumKit\Core\Support\UserModel;
use Illuminate\Support\Facades\Schema;

/**
 * A table's exact structure as the database reports it, normalised so it can be
 * compared across servers and table prefixes: the prefix is stripped from index and
 * foreign key names, references to the host's users table become `{users}`, integer
 * display widths are dropped, and defaults lose their quoting.
 *
 * @phpstan-type Column array{type: string, nullable: bool, default: string|null, collation: string|null, auto_increment: bool}
 * @phpstan-type Index array{columns: list<string>, unique: bool, primary: bool}
 * @phpstan-type ForeignKey array{columns: list<string>, on: string, foreign_columns: list<string>, on_delete: string, on_update: string}
 */
final readonly class TableShape
{
    /**
     * @param  array<string, Column>  $columns  In table order.
     * @param  array<string, Index>  $indexes
     * @param  array<string, ForeignKey>  $foreignKeys
     */
    public function __construct(
        public array $columns,
        public array $indexes,
        public array $foreignKeys,
    ) {}

    /**
     * Describe an existing table. Pass the base name; the configured prefix is applied.
     */
    public static function inspect(string $baseName): self
    {
        $table = Tables::name($baseName);
        $prefix = Tables::name('');

        $columns = [];

        foreach (Schema::getColumns($table) as $column) {
            $columns[$column['name']] = [
                'type' => self::normaliseType($column['type']),
                'nullable' => $column['nullable'],
                'default' => self::normaliseDefault($column['default']),
                'collation' => $column['collation'],
                'auto_increment' => $column['auto_increment'],
            ];
        }

        $indexes = [];

        foreach (Schema::getIndexes($table) as $index) {
            $indexes[self::stripPrefix($index['name'], $prefix)] = [
                'columns' => $index['columns'],
                'unique' => $index['unique'],
                'primary' => $index['primary'],
            ];
        }

        ksort($indexes);

        $foreignKeys = [];
        $usersTable = UserModel::table();

        foreach (Schema::getForeignKeys($table) as $key) {
            $foreignKeys[self::stripPrefix($key['name'], $prefix)] = [
                'columns' => $key['columns'],
                'on' => $key['foreign_table'] === $usersTable ? '{users}' : self::stripPrefix($key['foreign_table'], $prefix),
                'foreign_columns' => $key['foreign_columns'],
                'on_delete' => strtolower($key['on_delete']),
                'on_update' => strtolower($key['on_update']),
            ];
        }

        ksort($foreignKeys);

        return new self($columns, $indexes, $foreignKeys);
    }

    /**
     * @param  array{columns: array<string, Column>, indexes: array<string, Index>, foreign_keys: array<string, ForeignKey>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['columns'], $data['indexes'], $data['foreign_keys']);
    }

    /**
     * @return array{columns: array<string, Column>, indexes: array<string, Index>, foreign_keys: array<string, ForeignKey>}
     */
    public function toArray(): array
    {
        return ['columns' => $this->columns, 'indexes' => $this->indexes, 'foreign_keys' => $this->foreignKeys];
    }

    /**
     * What differs between this (the actual table) and the expected shape. Empty when
     * they match. Column order is only compared when asked, because it does not change
     * how the application behaves.
     *
     * @return list<string>
     */
    public function differencesFrom(self $expected, bool $compareColumnOrder = false): array
    {
        $differences = [];

        foreach ($expected->columns as $name => $column) {
            if (! isset($this->columns[$name])) {
                $differences[] = "column {$name} is missing";

                continue;
            }

            foreach ($column as $property => $value) {
                $actual = $this->columns[$name][$property];

                if ($actual !== $value) {
                    $differences[] = "column {$name} {$property} is ".self::show($actual).', expected '.self::show($value);
                }
            }
        }

        foreach (array_diff(array_keys($this->columns), array_keys($expected->columns)) as $name) {
            $differences[] = "column {$name} is not expected";
        }

        if ($compareColumnOrder && array_keys($this->columns) !== array_keys($expected->columns)) {
            $differences[] = 'column order is '.implode(', ', array_keys($this->columns)).'; expected '.implode(', ', array_keys($expected->columns));
        }

        foreach (self::compareNamed('index', $this->indexes, $expected->indexes) as $difference) {
            $differences[] = $difference;
        }

        foreach (self::compareNamed('foreign key', $this->foreignKeys, $expected->foreignKeys) as $difference) {
            $differences[] = $difference;
        }

        return $differences;
    }

    /**
     * @param  array<string, array<string, mixed>>  $actual
     * @param  array<string, array<string, mixed>>  $expected
     * @return list<string>
     */
    private static function compareNamed(string $kind, array $actual, array $expected): array
    {
        $differences = [];

        foreach ($expected as $name => $definition) {
            if (! isset($actual[$name])) {
                $differences[] = "{$kind} {$name} is missing";

                continue;
            }

            foreach ($definition as $property => $value) {
                if ($actual[$name][$property] !== $value) {
                    $differences[] = "{$kind} {$name} {$property} is ".self::show($actual[$name][$property]).', expected '.self::show($value);
                }
            }
        }

        foreach (array_diff(array_keys($actual), array_keys($expected)) as $name) {
            $differences[] = "{$kind} {$name} is not expected";
        }

        return $differences;
    }

    private static function show(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_array($value) => '['.implode(', ', array_map(self::show(...), $value)).']',
            default => (string) $value,
        };
    }

    /**
     * Drop integer display widths (`bigint(20) unsigned` becomes `bigint unsigned`), which
     * newer servers no longer report, but keep `tinyint(1)`, which marks a boolean.
     */
    private static function normaliseType(string $type): string
    {
        $type = strtolower($type);

        if ($type === 'tinyint(1)') {
            return $type;
        }

        return (string) preg_replace('/^((?:tiny|small|medium|big)?int)\(\d+\)/', '$1', $type);
    }

    private static function normaliseDefault(mixed $default): ?string
    {
        if ($default === null) {
            return null;
        }

        $default = trim((string) $default);

        if (strcasecmp($default, 'NULL') === 0) {
            return null;
        }

        return trim($default, "'");
    }

    private static function stripPrefix(string $name, string $prefix): string
    {
        return $prefix !== '' && str_starts_with($name, $prefix) ? substr($name, strlen($prefix)) : $name;
    }
}
