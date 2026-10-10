<?php

declare(strict_types=1);

use ForumKit\Core\Schema\Adopter;
use ForumKit\Core\Schema\AdoptionStatus;
use ForumKit\Core\Schema\PackageSchema;
use ForumKit\Core\Schema\SchemaRegistry;
use ForumKit\Core\Schema\TableShape;
use ForumKit\Core\Support\Tables;
use ForumKit\Core\Support\UserKey;
use ForumKit\Core\Support\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * A stand-in for a site's existing tables: `things` with a unique key, an index and a
 * foreign key into users, and `thing_notes` pointing at things.
 */
function createThingTables(string $valueType = 'text'): void
{
    Schema::create(Tables::name('things'), function (Blueprint $table) use ($valueType): void {
        $table->id();
        UserKey::column($table)->constrained(UserModel::table())->cascadeOnDelete();
        $table->string('key', 120)->unique();
        $table->{$valueType}('value')->nullable();
        $table->boolean('is_public')->default(false)->index();
        $table->timestamps();
    });

    Schema::create(Tables::name('thing_notes'), function (Blueprint $table): void {
        $table->id();
        $table->foreignId('thing_id')->constrained(Tables::name('things'))->cascadeOnDelete();
        $table->text('note');
    });
}

function dropThingTables(): void
{
    Schema::dropIfExists(Tables::name('thing_notes'));
    Schema::dropIfExists(Tables::name('things'));
}

/**
 * Snapshot the stand-in tables (as the package author would) and register them with two
 * migration files in a temporary directory.
 */
function registerThingsPackage(): PackageSchema
{
    $dir = sys_get_temp_dir().'/forumkit-adopt-'.uniqid();
    File::makeDirectory($dir);
    File::put($dir.'/2026_01_01_000001_create_things_table.php', '<?php');
    File::put($dir.'/2026_01_01_000002_create_thing_notes_table.php', '<?php');

    $schema = new PackageSchema('things', [
        'things' => TableShape::inspect('things'),
        'thing_notes' => TableShape::inspect('thing_notes'),
    ], $dir);

    app(SchemaRegistry::class)->register($schema);

    return $schema;
}

function recordedMigrations(): array
{
    return DB::table('migrations')->where('migration', 'like', '2026_01_01_%')->orderBy('migration')->pluck('migration')->all();
}

beforeEach(function () {
    $this->loadLaravelMigrations();
    createThingTables();
    $this->schema = registerThingsPackage();
    // Pretend the migrations were never run: the tables came from elsewhere.
    DB::table('migrations')->where('migration', 'like', '2026_01_01_%')->delete();
});

afterEach(function () {
    dropThingTables();
    File::deleteDirectory($this->schema->migrationsPath);
});

it('reports a package whose tables match as adoptable', function () {
    $plan = app(Adopter::class)->planFor($this->schema);

    expect($plan->status)->toBe(AdoptionStatus::Adoptable)
        ->and($plan->differences)->toBe([])
        ->and($plan->pendingMigrations)->toBe(['2026_01_01_000001_create_things_table', '2026_01_01_000002_create_thing_notes_table']);
});

it('records nothing on a dry run', function () {
    $this->artisan('forumkit:adopt', ['--dry-run' => true])
        ->expectsOutputToContain('things: adoptable')
        ->expectsOutputToContain('Dry run: nothing was recorded.')
        ->assertExitCode(0);

    expect(recordedMigrations())->toBe([]);
});

it('records the package migrations as run in one new batch, and only once', function () {
    $before = (int) DB::table('migrations')->max('batch');

    $this->artisan('forumkit:adopt')->expectsOutputToContain('Recorded 2 migration(s) as run.')->assertExitCode(0);

    expect(recordedMigrations())->toBe(['2026_01_01_000001_create_things_table', '2026_01_01_000002_create_thing_notes_table'])
        ->and(DB::table('migrations')->where('migration', 'like', '2026_01_01_%')->pluck('batch')->unique()->all())->toBe([$before + 1]);

    $this->artisan('forumkit:adopt')
        ->expectsOutputToContain('things: already adopted')
        ->expectsOutputToContain('Recorded 0 migration(s) as run.')
        ->assertExitCode(0);

    expect(recordedMigrations())->toHaveCount(2);
});

it('never changes the tables or their data', function () {
    DB::table(Tables::name('things'))->insert(['user_id' => DB::table('users')->insertGetId(['name' => 'U', 'email' => 'u@example.test', 'password' => 'x']), 'key' => 'k', 'value' => 'v']);
    $before = TableShape::inspect('things')->toArray();

    $this->artisan('forumkit:adopt')->assertExitCode(0);

    expect(TableShape::inspect('things')->toArray())->toBe($before)
        ->and(DB::table(Tables::name('things'))->value('value'))->toBe('v');
});

it('refuses on a column that differs and records nothing', function () {
    dropThingTables();
    createThingTables(valueType: 'string');

    $this->artisan('forumkit:adopt')
        ->expectsOutputToContain('things: mismatch')
        ->expectsOutputToContain('things: column value type is varchar(255), expected text')
        ->expectsOutputToContain('Nothing was recorded')
        ->assertExitCode(1);

    expect(recordedMigrations())->toBe([]);
});

it('refuses when an index or foreign key is missing or extra', function () {
    Schema::table(Tables::name('things'), function (Blueprint $table): void {
        $table->dropIndex(Tables::name('things').'_is_public_index');
        $table->index('value', Tables::name('things').'_value_index');
    });
    Schema::table(Tables::name('thing_notes'), fn (Blueprint $table) => $table->dropForeign(['thing_id']));

    $plan = app(Adopter::class)->planFor($this->schema);

    expect($plan->status)->toBe(AdoptionStatus::Mismatch)
        ->and($plan->differences)->toContain('things: index things_is_public_index is missing')
        ->and($plan->differences)->toContain('things: index things_value_index is not expected')
        ->and($plan->differences)->toContain('thing_notes: foreign key thing_notes_thing_id_foreign is missing');
});

it('refuses when a column is missing or unexpected, and ignores column order unless asked', function () {
    Schema::table(Tables::name('things'), function (Blueprint $table): void {
        $table->dropColumn('is_public');
        $table->string('extra')->nullable();
    });

    $differences = app(Adopter::class)->planFor($this->schema)->differences;

    expect($differences)->toContain('things: column is_public is missing')
        ->and($differences)->toContain('things: column extra is not expected');
});

it('refuses a package whose tables are only partly present', function () {
    Schema::drop(Tables::name('thing_notes'));

    $plan = app(Adopter::class)->planFor($this->schema);

    expect($plan->status)->toBe(AdoptionStatus::Partial)
        ->and($plan->differences)->toBe(['thing_notes: table is missing while other tables of this package exist']);

    $this->artisan('forumkit:adopt')->assertExitCode(1);

    expect(recordedMigrations())->toBe([]);
});

it('tells a fresh install to migrate instead', function () {
    dropThingTables();

    $this->artisan('forumkit:adopt')
        ->expectsOutputToContain('things: fresh install')
        ->expectsOutputToContain('run `php artisan migrate`')
        ->assertExitCode(0);

    expect(recordedMigrations())->toBe([]);
});

it('adopts tables that have no prefix from a snapshot taken with the default prefix', function () {
    // The snapshot was taken against forumkit_ tables. An adopting site has bare names.
    dropThingTables();
    config(['forumkit.table_prefix' => '']);
    createThingTables();

    $plan = app(Adopter::class)->planFor($this->schema);

    expect($plan->status)->toBe(AdoptionStatus::Adoptable, implode('; ', $plan->differences));

    dropThingTables();
});

it('limits the run to one package', function () {
    $this->artisan('forumkit:adopt', ['--package' => 'nothing-here'])
        ->expectsOutputToContain('No packages with a schema snapshot are installed.')
        ->assertExitCode(0);

    $this->artisan('forumkit:adopt', ['--package' => 'things', '--dry-run' => true])
        ->expectsOutputToContain('things: adoptable')
        ->assertExitCode(0);
});

it('will not apply a plan that is not adoptable', function () {
    dropThingTables();
    $plan = app(Adopter::class)->planFor($this->schema);

    app(Adopter::class)->apply($plan);
})->throws(LogicException::class, 'Cannot adopt [things]: fresh install.');

it('describes tables independently of the prefix, display widths and quoting', function () {
    $shape = TableShape::inspect('things');

    expect($shape->columns['id']['type'])->toBe('bigint unsigned')
        ->and($shape->columns['is_public']['type'])->toBe('tinyint(1)')
        ->and($shape->columns['is_public']['default'])->toBe('0')
        ->and($shape->columns['value']['default'])->toBeNull()
        ->and($shape->columns['id']['auto_increment'])->toBeTrue()
        ->and(array_keys($shape->indexes))->toBe(['primary', 'things_is_public_index', 'things_key_unique', 'things_user_id_foreign'])
        ->and($shape->foreignKeys['things_user_id_foreign'])->toBe([
            'columns' => ['user_id'],
            'on' => '{users}',
            'foreign_columns' => ['id'],
            'on_delete' => 'cascade',
            'on_update' => 'restrict',
        ])
        ->and(TableShape::inspect('thing_notes')->foreignKeys['thing_notes_thing_id_foreign']['on'])->toBe('things');
});

it('can compare column order when asked', function () {
    $actual = TableShape::inspect('things');
    $reordered = new TableShape(array_reverse($actual->columns, true), $actual->indexes, $actual->foreignKeys);

    expect($actual->differencesFrom($reordered))->toBe([])
        ->and($actual->differencesFrom($reordered, compareColumnOrder: true))->toHaveCount(1);
});

it('dumps a snapshot that loads back to the same shape', function () {
    $path = sys_get_temp_dir().'/forumkit-snapshot-'.uniqid().'.php';

    $this->artisan('forumkit:schema:dump', ['tables' => ['things', 'thing_notes'], '--path' => $path])->assertExitCode(0);

    $loaded = PackageSchema::fromFile('things', $path, $this->schema->migrationsPath);

    expect($loaded->tables['things']->toArray())->toBe(TableShape::inspect('things')->toArray())
        ->and($loaded->tables['thing_notes']->toArray())->toBe(TableShape::inspect('thing_notes')->toArray())
        ->and(file_get_contents($path))->toContain('declare(strict_types=1);')->not->toContain('array (');

    File::delete($path);
});

it('rejects a snapshot file that does not return tables', function () {
    $path = sys_get_temp_dir().'/forumkit-bad-'.uniqid().'.php';
    File::put($path, '<?php return [];');

    PackageSchema::fromFile('x', $path, '/tmp');
})->throws(InvalidArgumentException::class, "must return an array with a 'tables' key");
