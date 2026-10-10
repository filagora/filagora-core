<?php

declare(strict_types=1);

namespace ForumKit\Core\Support;

use ForumKit\Core\ForumKit;

/**
 * Resolves prefixed table names. Always pass the base name, for example
 * `Tables::name('system_settings')`, in migrations and in models' `$table`.
 */
final class Tables
{
    public static function name(string $base): string
    {
        return ForumKit::tablePrefix().$base;
    }
}
