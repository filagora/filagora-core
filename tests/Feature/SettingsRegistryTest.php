<?php

declare(strict_types=1);

use Filagora\Core\Events\SettingChanged;
use Filagora\Core\Models\Setting;
use Filagora\Core\Settings\SettingDefinition;
use Filagora\Core\Settings\SettingsRegistry;
use Filagora\Core\Settings\SettingType;
use Filagora\Core\Support\Models;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function settings(): SettingsRegistry
{
    return app(SettingsRegistry::class);
}

it('falls back to the caller default when nothing is defined or stored', function () {
    expect(settings()->get('missing', 'fallback'))->toBe('fallback')
        ->and(settings()->get('  ', 'blank'))->toBe('blank');
});

it('uses the definition default until a row exists', function () {
    settings()->define(new SettingDefinition('site.name', default: 'Filagora'));

    expect(settings()->string('site.name'))->toBe('Filagora');

    Setting::create(['key' => 'site.name', 'value_type' => 'string', 'value' => 'My Forum']);
    settings()->flush();

    expect(settings()->string('site.name'))->toBe('My Forum');
});

it('casts stored values to their declared types', function () {
    Setting::create(['key' => 'a.int', 'value_type' => 'integer', 'value' => '42']);
    Setting::create(['key' => 'a.bool', 'value_type' => 'boolean', 'value' => '1']);
    Setting::create(['key' => 'a.off', 'value_type' => 'boolean', 'value' => '0']);
    Setting::create(['key' => 'a.json', 'value_type' => 'json', 'value' => '{"x":[1,2]}']);

    expect(settings()->integer('a.int'))->toBe(42)
        ->and(settings()->boolean('a.bool'))->toBeTrue()
        ->and(settings()->boolean('a.off', true))->toBeFalse()
        ->and(settings()->get('a.json'))->toBe(['x' => [1, 2]]);
});

it('set() creates a row typed by the definition and round-trips', function () {
    settings()->define(new SettingDefinition('forums.per_page', SettingType::Integer, 20, label: 'Per page', public: true));

    settings()->set('forums.per_page', 50);

    $row = Setting::where('key', 'forums.per_page')->sole();

    expect($row->value)->toBe('50')
        ->and($row->value_type)->toBe(SettingType::Integer)
        ->and($row->label)->toBe('Per page')
        ->and($row->is_public)->toBeTrue()
        ->and(settings()->integer('forums.per_page'))->toBe(50);
});

it('set() updates an existing row without losing its public flag', function () {
    Setting::create(['key' => 'k', 'value_type' => 'string', 'value' => 'a', 'is_public' => true]);

    settings()->set('k', 'b');

    $row = Setting::where('key', 'k')->sole();

    expect($row->value)->toBe('b')->and($row->is_public)->toBeTrue();
});

it('drops memoised values when a setting changes', function () {
    Setting::create(['key' => 'm', 'value_type' => 'string', 'value' => 'one']);

    expect(settings()->string('m'))->toBe('one');

    Setting::where('key', 'm')->sole()->update(['value' => 'two']);

    expect(settings()->string('m'))->toBe('two');
});

it('dispatches SettingChanged on save and delete', function () {
    Event::fake([SettingChanged::class]);

    $setting = Setting::create(['key' => 'e', 'value_type' => 'string', 'value' => 'x']);
    $setting->delete();

    Event::assertDispatchedTimes(SettingChanged::class, 2);
});

it('uses the prefixed table and honours an empty prefix name', function () {
    expect((new Setting)->getTable())->toBe('filagora_system_settings');
});

it('scopes public settings', function () {
    Setting::create(['key' => 'p', 'value_type' => 'string', 'value' => 'x', 'is_public' => true]);
    Setting::create(['key' => 'q', 'value_type' => 'string', 'value' => 'y']);

    expect(Setting::public()->pluck('key')->all())->toBe(['p']);
});

it('lets a host swap the model class', function () {
    config(['filagora.models.setting' => Setting::class]);

    expect(Models::class('setting'))->toBe(Setting::class);
});

it('keeps the declared definitions when the request scope is reset, as a queue worker does between jobs', function () {
    settings()->define(new SettingDefinition('worker.key', SettingType::Integer, 7));

    expect(settings()->integer('worker.key'))->toBe(7);

    Setting::create(['key' => 'worker.key', 'value_type' => 'integer', 'value' => '9']);
    expect(settings()->integer('worker.key'))->toBe(9);

    app()->forgetScopedInstances();

    // The memo is gone (a new scope reads the database again) but the definition is not.
    expect(settings()->definition('worker.key'))->not->toBeNull()
        ->and(settings()->integer('worker.key'))->toBe(9);

    Setting::where('key', 'worker.key')->delete();
    app()->forgetScopedInstances();

    expect(settings()->integer('worker.key'))->toBe(7)
        ->and(array_keys(settings()->definitions()))->toBe(['worker.key']);
});
