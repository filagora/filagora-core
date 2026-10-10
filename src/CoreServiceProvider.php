<?php

declare(strict_types=1);

namespace ForumKit\Core;

use ForumKit\Core\Events\SettingChanged;
use ForumKit\Core\Modules\Module;
use ForumKit\Core\Modules\ModuleRegistry;
use ForumKit\Core\Navigation\NavigationGroupDefinition;
use ForumKit\Core\Navigation\NavigationGroupRegistry;
use ForumKit\Core\Settings\SettingsRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/forumkit.php', 'forumkit');

        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(NavigationGroupRegistry::class);
        $this->app->scoped(SettingsRegistry::class);
    }

    public function boot(): void
    {
        $this->app->make(ModuleRegistry::class)->register(
            new Module('core', 'ForumKit Core', 'zencart/forumkit-core'),
        );

        $this->app->make(NavigationGroupRegistry::class)->register(
            new NavigationGroupDefinition(
                key: 'system',
                label: fn (): string => __('forumkit::messages.navigation_groups.system'),
                icon: 'heroicon-o-server-stack',
                sort: 900,
            ),
        );

        Event::listen(SettingChanged::class, fn () => $this->app->make(SettingsRegistry::class)->flush());

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'forumkit');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'forumkit');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/forumkit.php' => config_path('forumkit.php'),
            ], 'forumkit-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'forumkit-migrations');
        }
    }
}
