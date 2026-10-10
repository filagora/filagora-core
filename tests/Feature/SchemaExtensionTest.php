<?php

declare(strict_types=1);

use ForumKit\Core\Schema\Adopter;
use ForumKit\Core\Schema\AdoptionStatus;
use ForumKit\Core\Schema\PackageSchema;
use ForumKit\Core\Schema\SchemaExtension;
use ForumKit\Core\Schema\SchemaRegistry;
use ForumKit\Core\Schema\TableShape;
use ForumKit\Core\Support\Tables;
use ForumKit\Core\Support\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

const GADGET_ACTIVITY = 'case when `last_post_at` is null or `last_post_at` < `created_at` then `created_at` else `last_post_at` end';

/**
 * The clean table a package would own: a generated column and a FULLTEXT index, like a
 * forum's threads table.
 */
function createGadgets(string $activity = GADGET_ACTIVITY): void
{
    Schema::create(Tables::name('gadgets'), function (Blueprint $table) use ($activity): void {
        $table->id();
        $table->string('title');
        $table->timestamp('created_at')->nullable();
        $table->timestamp('last_post_at')->nullable();
        $table->dateTime('activity_at')->virtualAs($activity);
        $table->index('activity_at', Tables::name('gadgets').'_activity_idx');
        $table->fullText('title');
    });
}

/**
 * What a particular site adds to that table.
 */
function addSiteExtras(): void
{
    Schema::table(Tables::name('gadgets'), function (Blueprint $table): void {
        $table->unsignedBigInteger('legacy_id')->nullable();
        $table->unique('legacy_id', Tables::name('gadgets').'_legacy_id_unique');
        $table->unsignedBigInteger('owner_id')->nullable();
        $table->foreign('owner_id', Tables::name('gadgets').'_owner_id_foreign')->references('id')->on(UserModel::table())->nullOnDelete();
    });
}

function gadgetsPackage(): PackageSchema
{
    $dir = sys_get_temp_dir().'/forumkit-ext-'.uniqid();
    File::makeDirectory($dir);
    File::put($dir.'/2026_01_01_000001_create_gadgets_table.php', '<?php');

    $schema = new PackageSchema('gadgets', ['gadgets' => TableShape::inspect('gadgets')], $dir);
    app(SchemaRegistry::class)->register($schema);

    return $schema;
}

function siteExtensionFile(): string
{
    $path = sys_get_temp_dir().'/forumkit-ext-snapshot-'.uniqid().'.php';
    test()->artisan('forumkit:schema:dump-extension', ['module' => 'gadgets', 'tables' => ['gadgets'], '--path' => $path])->assertExitCode(0);

    return $path;
}

function siteMigrations(): string
{
    $dir = sys_get_temp_dir().'/forumkit-site-migrations-'.uniqid();
    File::makeDirectory($dir);
    File::put($dir.'/2026_02_01_000001_add_site_columns_to_gadgets.php', '<?php');

    return $dir;
}

beforeEach(function () {
    $this->loadLaravelMigrations();
    $this->dirs = [];
    createGadgets();
    $this->package = gadgetsPackage();
    $this->dirs[] = $this->package->migrationsPath;
    DB::table('migrations')->whereIn('migration', ['2026_01_01_000001_create_gadgets_table', '2026_02_01_000001_add_site_columns_to_gadgets'])->delete();
});

afterEach(function () {
    Schema::dropIfExists(Tables::name('gadgets'));
    foreach ($this->dirs as $dir) {
        File::deleteDirectory($dir);
    }
});

// ------------------------------------------------------------ generated columns

it('reports a generated column with its kind and normalised expression', function () {
    $column = TableShape::inspect('gadgets')->columns['activity_at'];

    expect($column['generation'])->toBe([
        'type' => 'virtual',
        'expression' => 'case when last_post_at is null or last_post_at < created_at then created_at else last_post_at end',
    ])->and(TableShape::inspect('gadgets')->columns['title']['generation'])->toBeNull();
});

it('catches a generated column with a different expression', function () {
    $expected = TableShape::inspect('gadgets');

    Schema::drop(Tables::name('gadgets'));
    createGadgets('`created_at`');

    $differences = TableShape::inspect('gadgets')->differencesFrom($expected);

    expect($differences)->toHaveCount(1)
        ->and($differences[0])->toStartWith('column activity_at generation is {"type":"virtual","expression":"created_at"}, expected {"type":"virtual","expression":"case when');
});

it('catches a plain column where a generated one is expected', function () {
    $expected = TableShape::inspect('gadgets');

    Schema::table(Tables::name('gadgets'), fn (Blueprint $table) => $table->dropColumn('activity_at'));
    Schema::table(Tables::name('gadgets'), fn (Blueprint $table) => $table->dateTime('activity_at')->nullable());

    expect(implode('; ', TableShape::inspect('gadgets')->differencesFrom($expected)))
        ->toContain('column activity_at generation is null, expected {"type":"virtual"');
});

// ---------------------------------------------------------------- index types

it('reports the index type and catches a FULLTEXT index that became a plain index', function () {
    $expected = TableShape::inspect('gadgets');

    expect($expected->indexes['gadgets_title_fulltext']['type'])->toBe('fulltext')
        ->and($expected->indexes['gadgets_activity_idx']['type'])->toBe('btree');

    Schema::table(Tables::name('gadgets'), function (Blueprint $table): void {
        $table->dropFullText(Tables::name('gadgets').'_title_fulltext');
        $table->index('title', Tables::name('gadgets').'_title_fulltext');
    });

    expect(TableShape::inspect('gadgets')->differencesFrom($expected))
        ->toBe(['index gadgets_title_fulltext type is btree, expected fulltext']);
});

it('still accepts a snapshot written before generation and index type were recorded', function () {
    $old = TableShape::inspect('gadgets')->toArray();

    foreach ($old['columns'] as $name => $column) {
        unset($old['columns'][$name]['generation']);
    }

    foreach ($old['indexes'] as $name => $index) {
        unset($old['indexes'][$name]['type']);
    }

    expect(TableShape::inspect('gadgets')->differencesFrom(TableShape::fromArray($old)))->toBe([]);
});

// ------------------------------------------------------------------ extensions

it('refuses a table that carries extras nobody declared', function () {
    addSiteExtras();

    $plan = app(Adopter::class)->planFor($this->package);

    expect($plan->status)->toBe(AdoptionStatus::Mismatch)
        ->and($plan->differences)->toContain('gadgets: column legacy_id is not expected')
        ->and($plan->differences)->toContain('gadgets: column owner_id is not expected')
        ->and($plan->differences)->toContain('gadgets: index gadgets_legacy_id_unique is not expected')
        ->and($plan->differences)->toContain('gadgets: foreign key gadgets_owner_id_foreign is not expected');
});

it('adopts a table whose extras are declared by an extension, exactly', function () {
    addSiteExtras();
    $migrations = siteMigrations();
    $this->dirs[] = $migrations;
    app(SchemaRegistry::class)->extend(SchemaExtension::fromFile('site', 'gadgets', siteExtensionFile(), $migrations));

    $plan = app(Adopter::class)->planFor($this->package);

    expect($plan->status)->toBe(AdoptionStatus::Adoptable, implode('; ', $plan->differences))
        ->and($plan->pendingMigrations)->toBe(['2026_01_01_000001_create_gadgets_table', '2026_02_01_000001_add_site_columns_to_gadgets']);

    $this->artisan('forumkit:adopt')->expectsOutputToContain('Recorded 2 migration(s) as run.')->assertExitCode(0);

    expect(app(Adopter::class)->planFor($this->package)->status)->toBe(AdoptionStatus::AlreadyAdopted);
});

it('still checks a declared extra exactly', function () {
    addSiteExtras();
    app(SchemaRegistry::class)->extend(SchemaExtension::fromFile('site', 'gadgets', siteExtensionFile()));

    Schema::table(Tables::name('gadgets'), fn (Blueprint $table) => $table->string('legacy_id', 10)->nullable()->change());

    $plan = app(Adopter::class)->planFor($this->package);

    expect($plan->status)->toBe(AdoptionStatus::Mismatch)
        ->and($plan->differences)->toContain('gadgets: column legacy_id type is varchar(10), expected bigint unsigned');
});

it('refuses when a declared extra is missing from the table', function () {
    addSiteExtras();
    app(SchemaRegistry::class)->extend(SchemaExtension::fromFile('site', 'gadgets', siteExtensionFile()));

    Schema::table(Tables::name('gadgets'), function (Blueprint $table): void {
        $table->dropForeign(Tables::name('gadgets').'_owner_id_foreign');
        $table->dropColumn('owner_id');
    });

    $plan = app(Adopter::class)->planFor($this->package);

    expect($plan->status)->toBe(AdoptionStatus::Mismatch)
        ->and($plan->differences)->toContain('gadgets: column owner_id is missing')
        ->and($plan->differences)->toContain('gadgets: foreign key gadgets_owner_id_foreign is missing');
});

it('ignores an extension for a module that is not installed', function () {
    addSiteExtras();
    $file = siteExtensionFile();
    $registry = app(SchemaRegistry::class);
    $registry->extend(SchemaExtension::fromFile('site', 'not-installed', $file));

    expect($registry->extensionsFor('gadgets'))->toBe([])
        ->and(app(Adopter::class)->planFor($this->package)->status)->toBe(AdoptionStatus::Mismatch);
});

it('rejects an extension that redefines a column the package owns', function () {
    $extension = new SchemaExtension('site', 'gadgets', ['gadgets' => ['columns' => ['title' => TableShape::inspect('gadgets')->columns['title']]]]);

    $this->package->extendedBy([$extension]);
})->throws(InvalidArgumentException::class, 'redefines existing columns entry [title]');

it('rejects an extension for a table the package does not own', function () {
    $extension = new SchemaExtension('site', 'gadgets', ['other' => ['columns' => []]]);

    $this->package->extendedBy([$extension]);
})->throws(InvalidArgumentException::class, 'extends table [other], which module [gadgets] does not own');

it('dumps only what the table has beyond the package, and the file loads back', function () {
    addSiteExtras();
    $path = siteExtensionFile();

    $extension = SchemaExtension::fromFile('site', 'gadgets', $path);

    expect(array_keys($extension->tables['gadgets']))->toBe(['columns', 'indexes', 'foreign_keys'])
        ->and(array_keys($extension->tables['gadgets']['columns']))->toBe(['legacy_id', 'owner_id'])
        ->and(array_keys($extension->tables['gadgets']['indexes']))->toBe(['gadgets_legacy_id_unique', 'gadgets_owner_id_foreign'])
        ->and($extension->tables['gadgets']['foreign_keys']['gadgets_owner_id_foreign']['on'])->toBe('{users}')
        ->and(file_get_contents($path))->toContain('declare(strict_types=1);')->not->toContain('array (');
});

it('dumps nothing for a table that matches the package, and refuses unknown modules and tables', function () {
    $path = sys_get_temp_dir().'/forumkit-empty-'.uniqid().'.php';

    $this->artisan('forumkit:schema:dump-extension', ['module' => 'gadgets', 'tables' => ['gadgets'], '--path' => $path])->assertExitCode(0);
    expect(SchemaExtension::fromFile('site', 'gadgets', $path)->tables)->toBe([]);

    $this->artisan('forumkit:schema:dump-extension', ['module' => 'nope', 'tables' => ['gadgets']])
        ->expectsOutputToContain('No schema snapshot is registered for module [nope].')->assertExitCode(1);
    $this->artisan('forumkit:schema:dump-extension', ['module' => 'gadgets', 'tables' => ['elsewhere']])
        ->expectsOutputToContain('Module [gadgets] does not own table [elsewhere].')->assertExitCode(1);
});

it('rejects an extension file that does not return tables', function () {
    $path = sys_get_temp_dir().'/forumkit-bad-ext-'.uniqid().'.php';
    File::put($path, '<?php return [];');

    SchemaExtension::fromFile('site', 'gadgets', $path);
})->throws(InvalidArgumentException::class, "must return an array with a 'tables' key");

it('fails loudly, instead of describing nothing, when a table does not exist', function () {
    expect(fn () => TableShape::inspect('no_such_table'))
        ->toThrow(InvalidArgumentException::class, 'Table [forumkit_no_such_table] does not exist');

    $this->artisan('forumkit:schema:dump', ['tables' => ['no_such_table']])
        ->expectsOutputToContain('does not exist')->assertExitCode(1);

    Schema::drop(Tables::name('gadgets'));

    $this->artisan('forumkit:schema:dump-extension', ['module' => 'gadgets', 'tables' => ['gadgets']])
        ->expectsOutputToContain('Run the migrations first.')->assertExitCode(1);

    createGadgets();
});
