<?php

declare(strict_types=1);

namespace Filagora\Core;

use Filagora\Core\Commands\AdoptCommand;
use Filagora\Core\Commands\DoctorCommand;
use Filagora\Core\Commands\DumpSchemaCommand;
use Filagora\Core\Commands\DumpSchemaExtensionCommand;
use Filagora\Core\Commands\InstallCommand;
use Filagora\Core\Content\ExtensionRegistry;
use Filagora\Core\Content\MarkdownRenderer;
use Filagora\Core\Doctor\Checks\MigrationsCheck;
use Filagora\Core\Doctor\Checks\ModulesCheck;
use Filagora\Core\Doctor\Checks\PhpVersionCheck;
use Filagora\Core\Doctor\Checks\UserModelCheck;
use Filagora\Core\Doctor\Doctor;
use Filagora\Core\Events\SettingChanged;
use Filagora\Core\Modules\Module;
use Filagora\Core\Modules\ModuleRegistry;
use Filagora\Core\Navigation\NavigationGroupDefinition;
use Filagora\Core\Navigation\NavigationGroupRegistry;
use Filagora\Core\Privacy\PrivacyContributorRegistry;
use Filagora\Core\Schema\Adopter;
use Filagora\Core\Schema\PackageSchema;
use Filagora\Core\Schema\SchemaRegistry;
use Filagora\Core\Settings\SettingDefinitions;
use Filagora\Core\Settings\SettingsRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filagora.php', 'filagora');

        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(NavigationGroupRegistry::class);
        $this->app->singleton(SettingDefinitions::class);
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
            new Module('core', 'Filagora Core', 'filagora/filagora-core'),
        );

        $this->app->make(NavigationGroupRegistry::class)->register(
            new NavigationGroupDefinition(
                key: 'system',
                label: fn (): string => __('filagora::messages.navigation_groups.system'),
                icon: 'heroicon-o-server-stack',
                sort: 900,
            ),
        );

        Event::listen(SettingChanged::class, fn () => $this->app->make(SettingsRegistry::class)->flush());

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'filagora');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filagora');
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
                __DIR__.'/../config/filagora.php' => config_path('filagora.php'),
            ], 'filagora-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'filagora-migrations');
        }
    }
}
