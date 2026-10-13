<?php

declare(strict_types=1);

namespace Filagora\Core;

use Filagora\Core\Filament\Resources\Settings\SettingResource;
use Filagora\Core\Navigation\NavigationGroupRegistry;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilagoraCorePlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filagora-core';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            SettingResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        $panel->navigationGroups(app(NavigationGroupRegistry::class)->toFilament());
    }
}
