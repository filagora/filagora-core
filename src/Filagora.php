<?php

declare(strict_types=1);

namespace Filagora\Core;

use Illuminate\Database\Eloquent\Model;

/**
 * Entry point for host applications to adjust Filagora at boot time.
 */
final class Filagora
{
    /** @var class-string<Model>|null */
    private static ?string $userModel = null;

    /**
     * Use this class as the host's user model, taking precedence over config.
     *
     * @param  class-string<Model>  $class
     */
    public static function useUserModel(string $class): void
    {
        self::$userModel = $class;
    }

    /**
     * The table prefix applied to every Filagora table.
     */
    public static function tablePrefix(): string
    {
        $prefix = config('filagora.table_prefix', 'filagora_');

        return is_string($prefix) ? $prefix : '';
    }

    /**
     * The explicit user-model override, if one was set with useUserModel().
     *
     * @return class-string<Model>|null
     *
     * @internal
     */
    public static function userModelOverride(): ?string
    {
        return self::$userModel;
    }

    /**
     * Clear runtime overrides. Intended for tests.
     *
     * @internal
     */
    public static function flush(): void
    {
        self::$userModel = null;
    }
}
