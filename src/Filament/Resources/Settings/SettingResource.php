<?php

declare(strict_types=1);

namespace ForumKit\Core\Filament\Resources\Settings;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use ForumKit\Core\Filament\Resources\Settings\Pages\CreateSetting;
use ForumKit\Core\Filament\Resources\Settings\Pages\EditSetting;
use ForumKit\Core\Filament\Resources\Settings\Pages\ListSettings;
use ForumKit\Core\Filament\Resources\Settings\Schemas\SettingForm;
use ForumKit\Core\Filament\Resources\Settings\Tables\SettingsTable;
use ForumKit\Core\Models\Setting;
use ForumKit\Core\Navigation\NavigationGroupRegistry;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Staff screen for system settings. Every action is gated by one Gate ability,
 * `forumkit.settings.ability` in config. Nothing defines that ability by default,
 * so access is denied until the host (or the entitlements package) grants it.
 */
class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return app(NavigationGroupRegistry::class)->label('system');
    }

    public static function getNavigationLabel(): string
    {
        return __('forumkit::messages.settings.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('forumkit::messages.settings.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('forumkit::messages.settings.plural_model_label');
    }

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $ability = config('forumkit.settings.ability');

        if (is_string($ability) && $ability !== '' && Gate::allows($ability)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public static function form(Schema $schema): Schema
    {
        return SettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SettingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSettings::route('/'),
            'create' => CreateSetting::route('/create'),
            'edit' => EditSetting::route('/{record}/edit'),
        ];
    }
}
