<?php

declare(strict_types=1);

namespace Filagora\Core\Support;

use Filagora\Core\Filagora;

/**
 * Resolves prefixed table names. Always pass the base name, for example
 * `Tables::name('system_settings')`, in migrations and in models' `$table`.
 */
final class Tables
{
    public static function name(string $base): string
    {
        return Filagora::tablePrefix().$base;
    }
}
