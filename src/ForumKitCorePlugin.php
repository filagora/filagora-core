<?php

declare(strict_types=1);

namespace ForumKit\Core;

use Filament\Contracts\Plugin;
use Filament\Panel;
use ForumKit\Core\Filament\Resources\Settings\SettingResource;
use ForumKit\Core\Navigation\NavigationGroupRegistry;

class ForumKitCorePlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'forumkit-core';
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
