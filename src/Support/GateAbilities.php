<?php

declare(strict_types=1);

namespace Filagora\Core\Support;

use Filagora\Core\Contracts\AccessChecker;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

/**
 * Turns a package's `gates` config (`ability => key`) into Laravel Gate abilities that ask the
 * bound AccessChecker, so every package does it the same way.
 */
final class GateAbilities
{
    /**
     * Define each ability of a config map.
     */
    public static function define(string $configKey): void
    {
        foreach (self::map($configKey) as $ability => $key) {
            Gate::define($ability, static fn (?Authenticatable $user): bool => app(AccessChecker::class)->allows($user, $key));
        }
    }

    /**
     * The keys a config map points at, for a package that declares them somewhere (such as the
     * entitlement registry).
     *
     * @return list<string>
     */
    public static function keys(string $configKey): array
    {
        return array_values(self::map($configKey));
    }

    /**
     * @return array<string, string>
     */
    private static function map(string $configKey): array
    {
        $map = [];

        foreach ((array) config($configKey, []) as $ability => $key) {
            if (is_string($ability) && is_string($key) && $key !== '') {
                $map[$ability] = $key;
            }
        }

        return $map;
    }
}
