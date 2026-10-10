<?php

declare(strict_types=1);

namespace ForumKit\Core\Support;

use ForumKit\Core\Exceptions\InvalidConfiguration;
use ForumKit\Core\ForumKit;
use Illuminate\Database\Eloquent\Model;

/**
 * Resolves the host application's user model.
 *
 * Precedence: ForumKit::useUserModel(), then `forumkit.user_model`, then the
 * default auth provider's model.
 */
final class UserModel
{
    /**
     * @return class-string<Model>
     */
    public static function class(): string
    {
        $class = ForumKit::userModelOverride()
            ?? self::configured('forumkit.user_model')
            ?? self::configured('auth.providers.users.model')
            ?? throw InvalidConfiguration::userModelMissing();

        if (! class_exists($class)) {
            throw InvalidConfiguration::userModelNotFound($class);
        }

        if (! is_subclass_of($class, Model::class)) {
            throw InvalidConfiguration::userModelNotEloquent($class);
        }

        return $class;
    }

    public static function make(): Model
    {
        $class = self::class();

        return new $class;
    }

    /**
     * The host's users table name, for foreign keys.
     */
    public static function table(): string
    {
        return self::make()->getTable();
    }

    /**
     * The attribute that names a user in selects, tables and labels.
     */
    public static function titleAttribute(): string
    {
        return self::configured('forumkit.user_title_attribute') ?? 'name';
    }

    private static function configured(string $key): ?string
    {
        $value = config($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
