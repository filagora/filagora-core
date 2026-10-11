<?php

declare(strict_types=1);

namespace ForumKit\Core;

use ForumKit\Core\Commands\AdoptCommand;
use ForumKit\Core\Commands\DoctorCommand;
use ForumKit\Core\Commands\DumpSchemaCommand;
use ForumKit\Core\Commands\DumpSchemaExtensionCommand;
use ForumKit\Core\Commands\InstallCommand;
use ForumKit\Core\Content\ExtensionRegistry;
use ForumKit\Core\Content\MarkdownRenderer;
use ForumKit\Core\Doctor\Checks\MigrationsCheck;
use ForumKit\Core\Doctor\Checks\ModulesCheck;
use ForumKit\Core\Doctor\Checks\PhpVersionCheck;
use ForumKit\Core\Doctor\Checks\UserModelCheck;
use ForumKit\Core\Doctor\Doctor;
use ForumKit\Core\Events\SettingChanged;
use ForumKit\Core\Modules\Module;
use ForumKit\Core\Modules\ModuleRegistry;
use ForumKit\Core\Navigation\NavigationGroupDefinition;
use ForumKit\Core\Navigation\NavigationGroupRegistry;
use ForumKit\Core\Privacy\PrivacyContributorRegistry;
use ForumKit\Core\Schema\Adopter;
use ForumKit\Core\Schema\PackageSchema;
use ForumKit\Core\Schema\SchemaRegistry;
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
        $this->app->singleton(ExtensionRegistry::class);
        $this->app->singleton(MarkdownRenderer::class);
        $this->app->singleton(PrivacyContributorRegistry::class);
        $this->app->singleton(Doctor::class);
        $this->app->singleton(SchemaRegistry::class);
        $this->app->singleton(Adopter::class);
    }

    public function boot(): void
    {
        $this->app->make(ModuleRegistry::class)->register(
            new Module('core', 'ForumKit Core', 'zcwilt/forumkit-core'),
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

        $this->app->make(SchemaRegistry::class)->register(
            PackageSchema::fromFile('core', __DIR__.'/../database/schema.php', __DIR__.'/../database/migrations'),
        );

        $doctor = $this->app->make(Doctor::class);
        $doctor->register(new PhpVersionCheck);
        $doctor->register(new UserModelCheck);
        $doctor->register(new MigrationsCheck);
        $doctor->register($this->app->make(ModulesCheck::class));

        if ($this->app->runningInConsole()) {
            $this->commands([DoctorCommand::class, InstallCommand::class, AdoptCommand::class, DumpSchemaCommand::class, DumpSchemaExtensionCommand::class]);

            $this->publishes([
                __DIR__.'/../config/forumkit.php' => config_path('forumkit.php'),
            ], 'forumkit-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'forumkit-migrations');
        }
    }
}
