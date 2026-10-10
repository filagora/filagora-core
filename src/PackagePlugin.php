<?php

declare(strict_types=1);

namespace ForumKit\Example;

use Filament\Contracts\Plugin;
use Filament\Panel;

class PackagePlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'forumkit-example';
    }

    public function register(Panel $panel): void
    {
        // Register resources, pages and widgets here.
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
