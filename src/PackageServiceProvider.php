<?php

declare(strict_types=1);

namespace ForumKit\Example;

use Illuminate\Support\ServiceProvider;

class PackageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/forumkit-example.php', 'forumkit-example');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'forumkit-example');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'forumkit-example');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/forumkit-example.php' => config_path('forumkit-example.php'),
            ], 'forumkit-example-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'forumkit-example-migrations');
        }
    }
}
