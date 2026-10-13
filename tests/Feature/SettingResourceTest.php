<?php

declare(strict_types=1);

use Filagora\Core\Filament\Resources\Settings\Pages\CreateSetting;
use Filagora\Core\Filament\Resources\Settings\Pages\EditSetting;
use Filagora\Core\Filament\Resources\Settings\Pages\ListSettings;
use Filagora\Core\Filament\Resources\Settings\SettingResource;
use Filagora\Core\Models\Setting;
use Filagora\Core\Settings\SettingsRegistry;
use Filament\Facades\Filament;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->loadLaravelMigrations();

    Filament::setCurrentPanel('admin');

    $this->staff = (new class extends User
    {
        protected $table = 'users';

        protected $guarded = [];
    })->forceFill([
        'name' => 'Staff',
        'email' => 'staff@example.test',
        'password' => 'x',
    ]);
    $this->staff->save();
});

it('denies access when the ability is not defined', function () {
    actingAs($this->staff);

    expect(SettingResource::canViewAny())->toBeFalse();
});

it('denies a user the gate refuses', function () {
    Gate::define('filagora.settings.manage', fn () => false);
    actingAs($this->staff);

    expect(SettingResource::canViewAny())->toBeFalse()
        ->and(SettingResource::canCreate())->toBeFalse();
});

it('lists settings for a user the gate allows', function () {
    Gate::define('filagora.settings.manage', fn () => true);
    actingAs($this->staff);
    $settings = collect([
        Setting::create(['key' => 'a.one', 'value_type' => 'string', 'value' => 'x']),
        Setting::create(['key' => 'b.two', 'value_type' => 'integer', 'value' => '2']),
    ]);

    Livewire::test(ListSettings::class)
        ->assertCanSeeTableRecords($settings);
});

it('creates a typed setting', function () {
    Gate::define('filagora.settings.manage', fn () => true);
    actingAs($this->staff);

    Livewire::test(CreateSetting::class)
        ->fillForm(['key' => 'forums.per_page', 'value_type' => 'integer', 'value' => '25', 'is_public' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $row = Setting::where('key', 'forums.per_page')->sole();

    expect($row->typedValue())->toBe(25)->and($row->is_public)->toBeTrue();
});

it('requires a unique key and valid json', function () {
    Gate::define('filagora.settings.manage', fn () => true);
    actingAs($this->staff);
    Setting::create(['key' => 'dup', 'value_type' => 'string', 'value' => 'x']);

    Livewire::test(CreateSetting::class)
        ->fillForm(['key' => 'dup', 'value_type' => 'string'])
        ->call('create')
        ->assertHasFormErrors(['key' => 'unique']);

    Livewire::test(CreateSetting::class)
        ->fillForm(['key' => 'j', 'value_type' => 'json', 'value' => '{broken'])
        ->call('create')
        ->assertHasFormErrors(['value']);
});

it('edits a setting and the registry sees the new value', function () {
    Gate::define('filagora.settings.manage', fn () => true);
    actingAs($this->staff);
    $setting = Setting::create(['key' => 'site.name', 'value_type' => 'string', 'value' => 'Old']);

    Livewire::test(EditSetting::class, ['record' => $setting->getKey()])
        ->fillForm(['value' => 'New'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(SettingsRegistry::class)->string('site.name'))->toBe('New');
});

it('sits in the system navigation group', function () {
    expect(SettingResource::getNavigationGroup())->toBe('System');
});
