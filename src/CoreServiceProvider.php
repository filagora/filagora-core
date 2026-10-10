<?php

declare(strict_types=1);

namespace ForumKit\Core;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/forumkit.php', 'forumkit');
    }

    public function boot(): void
    {
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
