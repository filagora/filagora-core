<?php

declare(strict_types=1);

namespace ForumKit\Core\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;

/**
 * The single place migrations create a column that references a user.
 *
 * `0.x` supports bigint user keys only. Routing every user column through here
 * keeps a later UUID or ULID option a localised change.
 */
final class UserKey
{
    /**
     * Add an unsigned bigint column referencing a user. Chain `nullable()`,
     * `index()` or `constrained(UserModel::table())` as the table needs.
     */
    public static function column(Blueprint $table, string $name = 'user_id'): ForeignIdColumnDefinition
    {
        return $table->foreignId($name);
    }
}
