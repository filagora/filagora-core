<?php

declare(strict_types=1);

namespace Filagora\Core\Support;

use Filagora\Core\Models\Setting;
use Illuminate\Database\Eloquent\Model;

/**
 * Resolves swappable model classes. Packages never hard-code a model class: they
 * ask here, and a host swaps one under `filagora.models.<key>`.
 */
final class Models
{
    /**
     * @return class-string<Model>
     */
    public static function class(string $key): string
    {
        /** @var class-string<Model> $class */
        $class = config("filagora.models.{$key}") ?? self::defaults()[$key]
            ?? throw new \InvalidArgumentException("No Filagora model is registered for [{$key}].");

        return $class;
    }

    /**
     * @return array<string, class-string<Model>>
     */
    private static function defaults(): array
    {
        return ['setting' => Setting::class];
    }
}
