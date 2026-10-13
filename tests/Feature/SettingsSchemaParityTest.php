<?php

declare(strict_types=1);

use Filagora\Core\Models\Setting;
use Filagora\Core\Schema\Adopter;
use Filagora\Core\Schema\AdoptionStatus;
use Filagora\Core\Schema\SchemaRegistry;
use Filagora\Core\Schema\TableShape;
use Filagora\Core\Settings\SettingType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/*
 * The support site's production database already holds these tables, and the packages
 * must be able to adopt them as they are. This pins the shape (columns, types,
 * nullability, defaults and indexes) so a migration change cannot drift from it.
 */
it('matches the existing system_settings table shape', function () {
    config(['filagora.table_prefix' => '']);

    Schema::dropIfExists('filagora_system_settings');
    (require __DIR__.'/../../database/migrations/2026_10_10_000000_create_filagora_system_settings_table.php')->up();

    $columns = collect(Schema::getColumns('system_settings'))->keyBy('name');

    expect($columns->keys()->all())->toBe(['id', 'key', 'label', 'value', 'value_type', 'is_public', 'description', 'created_at', 'updated_at'])
        ->and($columns['id']['type'])->toBe('bigint(20) unsigned')
        ->and($columns['key']['type'])->toBe('varchar(120)')
        ->and($columns['label']['type'])->toBe('varchar(160)')
        ->and($columns['label']['nullable'])->toBeTrue()
        ->and($columns['value']['type_name'])->toBe('longtext')
        ->and($columns['value']['nullable'])->toBeTrue()
        ->and($columns['value_type']['type'])->toBe('varchar(20)')
        ->and($columns['value_type']['nullable'])->toBeFalse()
        ->and(trim((string) $columns['value_type']['default'], "'"))->toBe('string')
        ->and($columns['is_public']['type'])->toBe('tinyint(1)')
        ->and($columns['is_public']['nullable'])->toBeFalse()
        ->and((string) $columns['is_public']['default'])->toBe('0')
        ->and($columns['description']['type_name'])->toBe('text')
        ->and($columns['created_at']['nullable'])->toBeTrue()
        ->and($columns['updated_at']['nullable'])->toBeTrue();

    $indexes = collect(Schema::getIndexes('system_settings'))->keyBy('name');

    expect($indexes->keys()->sort()->values()->all())->toBe(['primary', 'system_settings_is_public_index', 'system_settings_key_unique'])
        ->and($indexes['system_settings_key_unique']['unique'])->toBeTrue()
        ->and($indexes['system_settings_is_public_index']['columns'])->toBe(['is_public']);

    Schema::drop('system_settings');
});

it('reads every value type already stored in the support site', function () {
    foreach (['string', 'text', 'integer', 'boolean', 'json', 'page'] as $type) {
        DB::table('filagora_system_settings')->insert(['key' => "legacy.{$type}", 'value_type' => $type, 'value' => null]);
    }

    $rows = Setting::query()->get();

    expect($rows)->toHaveCount(6)
        ->and($rows->pluck('value_type')->map->value->sort()->values()->all())->toBe(['boolean', 'integer', 'json', 'page', 'string', 'text']);
});

it('reads a page setting as a plain string', function () {
    Setting::create(['key' => 'privacy_page', 'value_type' => SettingType::Page, 'value' => '/privacy']);

    expect(Setting::where('key', 'privacy_page')->sole()->typedValue())->toBe('/privacy');
});

it('keeps the shipped snapshot in step with the migration, column order included', function () {
    $snapshot = app(SchemaRegistry::class)->get('core');

    expect($snapshot)->not->toBeNull()
        ->and(array_keys($snapshot->tables))->toBe(['system_settings'])
        ->and(TableShape::inspect('system_settings')->differencesFrom($snapshot->tables['system_settings'], compareColumnOrder: true))->toBe([])
        ->and($snapshot->migrationNames())->toBe(['2026_10_10_000000_create_filagora_system_settings_table']);
});

it('adopts an existing system_settings table that has no prefix', function () {
    Schema::drop('filagora_system_settings');
    config(['filagora.table_prefix' => '']);
    (require __DIR__.'/../../database/migrations/2026_10_10_000000_create_filagora_system_settings_table.php')->up();
    DB::table('migrations')->where('migration', '2026_10_10_000000_create_filagora_system_settings_table')->delete();

    $plan = app(Adopter::class)->planFor(app(SchemaRegistry::class)->get('core'));

    expect($plan->status)->toBe(AdoptionStatus::Adoptable, implode('; ', $plan->differences));

    Schema::drop('system_settings');
    config(['filagora.table_prefix' => 'filagora_']);
});
