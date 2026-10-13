# Filagora Core

The foundation of the Filagora packages: a Laravel 13 / Filament 5 plugin providing the
module and navigation registries, user-model and table-prefix resolution, system settings,
safe markdown rendering, a health check (`filagora:doctor`), and `filagora:adopt`, which lets
a database that already holds a package's tables start using the package.

> `0.x`: the API may change between minor versions.

## Requirements

PHP 8.4, Laravel 13 and Filament 5. Tested against MariaDB 11.4.

## Install

```bash
composer require filagora/filagora-core
php artisan filagora:install   # publishes the config, runs the migrations, runs filagora:doctor
```

or step by step:

```bash
php artisan vendor:publish --tag=filagora-config
php artisan migrate
php artisan filagora:doctor
```

Register the plugin on a Filament panel:

```php
->plugin(\Filagora\Core\FilagoraCorePlugin::make())
```

## What it provides

| Piece | Use |
| --- | --- |
| `Tables::name('forums')` | Prefixed table names (`filagora_` by default). Always use it in migrations and models |
| `UserModel`, `UserKey::column()` | Resolve the host's user model; create user foreign-key columns in one place |
| `ModuleRegistry` | Each package registers itself; `filagora:doctor` checks the dependency graph |
| `NavigationGroupRegistry` | Packages declare sidebar groups; hosts override label, icon, sort and collapsed state |
| `SettingsRegistry` + `SettingResource` | Typed key/value settings with package-declared defaults and a staff screen |
| `MarkdownRenderer` + `ExtensionRegistry` | Safe CommonMark/GFM rendering that packages extend |
| `PrivacyContributor` | Contract for exporting and erasing a user's data, for the planned compliance package |
| `Doctor` | Health checks that every package can add to |
| `SchemaRegistry`, `SchemaExtension` | Table snapshots that `filagora:adopt` checks, and extensions a profile package uses to declare a site's extra columns, indexes and foreign keys |

## Commands

| Command | What it does |
| --- | --- |
| `filagora:install` | Publishes the config, migrates and runs `filagora:doctor` |
| `filagora:doctor` | Checks PHP, the user model, the core tables and that every module's requirements are installed |
| `filagora:adopt` | Starts the installed packages on a database that already holds their tables: checks every table against the package's snapshot and, only if all match, records the package's migrations as run. `--dry-run` reports without recording; `--package=` limits it to one. It never alters tables or data |
| `filagora:schema:dump` | Writes a snapshot of tables (package authors; run after the migrations) |
| `filagora:schema:dump-extension` | Writes only what a table has beyond a package's own snapshot, as a schema extension |

## Configuration

`config/filagora.php` is merged with the host's copy, so list only what differs.
Environment variables are prefixed `FILAGORA_`.

| Key | Environment | Default | Meaning |
| --- | --- | --- | --- |
| `table_prefix` | `FILAGORA_TABLE_PREFIX` | `filagora_` | Prefix for every Filagora table. Set it before migrating. An empty string uses the bare table names, which is how an existing site adopts its tables |
| `user_model` | `FILAGORA_USER_MODEL` | the auth provider's model | The host's user model. Filagora never alters your `users` table |
| `user_title_attribute` | `FILAGORA_USER_TITLE_ATTRIBUTE` | `name` | The user attribute shown in selects, tables and labels, for example `username` |
| `models` | | `[]` | Swap a model class by key (`'setting' => App\Models\Setting::class`) |
| `markdown.line_breaks` | | `true` | A single newline becomes a visible line break |
| `markdown.max_nesting_level` | | `100` | CommonMark nesting limit |
| `markdown.internal_hosts` | | `[]` | Hosts whose links are not marked external (the `app.url` host is always included) |
| `settings.ability` | | `filagora.settings.manage` | The Gate ability that opens the settings screen |
| `navigation.groups` | | `[]` | Per-group overrides, keyed by group key: `label`, `icon`, `sort`, `collapsed` |

The settings screen is denied until the Gate ability is defined. With
[`filagora/filagora-entitlements`](https://github.com/filagora/filagora-entitlements) installed
it is mapped to an entitlement; without it, define it yourself:

```php
Gate::define('filagora.settings.manage', fn ($user) => $user->isAdmin());
```

## Extending

Extend through documented contracts, events and registries. Anything not documented
is `@internal`.

## Testing

```bash
composer test
```

Tests run against a real MariaDB, never SQLite, so engine behaviour (FULLTEXT,
`ONLY_FULL_GROUP_BY`, collation) matches production. Host and credentials come from
your environment; the database is always named `testing`, never your development one.

## Local development (DDEV)

`.ddev/` is included. `ddev start` creates both `db` and `testing` (run `ddev restart` once
if `testing` is missing), then `ddev exec vendor/bin/pest`.

## Licence

MIT. See [LICENSE](LICENSE).
