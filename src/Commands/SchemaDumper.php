<?php

declare(strict_types=1);

namespace Filagora\Core\Commands;

/**
 * Writes schema snapshots as PHP source: short array syntax, four-space indentation.
 */
final class SchemaDumper
{
    public static function export(mixed $value, int $depth = 0): string
    {
        if ($value === null) {
            return 'null';
        }

        if (! is_array($value)) {
            return var_export($value, true);
        }

        if ($value === []) {
            return '[]';
        }

        $pad = str_repeat('    ', $depth + 1);
        $isList = array_is_list($value);
        $lines = [];

        foreach ($value as $key => $item) {
            $prefix = $isList ? '' : var_export($key, true).' => ';
            $lines[] = $pad.$prefix.self::export($item, $depth + 1).',';
        }

        return "[\n".implode("\n", $lines)."\n".str_repeat('    ', $depth).']';
    }
}
