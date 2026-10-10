# ForumKit Core

The foundation of the ForumKit packages: a Laravel 13 / Filament 5 plugin providing the
module and navigation registries, user-model and table-prefix resolution, system settings,
markdown rendering and the `forumkit:doctor` and `forumkit:install` commands.

> `0.x`: the API may change between minor versions.

## Install

```bash
composer require zencart/forumkit-core
php artisan vendor:publish --tag=forumkit-config
```

Register the plugin on a Filament panel:

```php
->plugin(\ForumKit\Core\ForumKitCorePlugin::make())
```

## What it provides

| Piece | Use |
| --- | --- |
| `Tables::name('forums')` | Prefixed table names (`forumkit_` by default). Always use it in migrations and models |
| `UserModel`, `UserKey::column()` | Resolve the host's user model; create user foreign-key columns in one place |
| `ModuleRegistry` | Each package registers itself; `forumkit:doctor` checks the dependency graph |
| `NavigationGroupRegistry` | Packages declare sidebar groups; hosts override label, icon, sort and collapsed state |
| `SettingsRegistry` + `SettingResource` | Typed key/value settings with package-declared defaults and a staff screen |
| `MarkdownRenderer` + `ExtensionRegistry` | Safe CommonMark/GFM rendering that packages extend |
| `PrivacyContributor` | Contract for exporting and erasing a user's data (used by the compliance package) |
| `Doctor` | Health checks that every package can add to |
| `SchemaRegistry`, `SchemaExtension` | Table snapshots that `forumkit:adopt` checks, and extensions a profile package uses to declare a site's extra columns, indexes and foreign keys |

Commands: `php artisan forumkit:install` publishes the config, migrates and runs
`forumkit:doctor`. `forumkit:adopt` starts a package on a database that already holds its
tables; `forumkit:schema:dump` and `forumkit:schema:dump-extension` write the snapshots it checks
(run them after the migrations).

## Configuration

`config/forumkit.php` is merged with the host's copy, so list only what
differs. Environment variables are prefixed `FORUMKIT_`.

The settings screen is gated by the Gate ability in `forumkit.settings.ability`
(`forumkit.settings.manage`). Nothing defines it by default, so access is denied
until you do:

```php
Gate::define('forumkit.settings.manage', fn ($user) => $user->isAdmin());
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

`.ddev/` is included. `ddev start` creates both `db` and `testing`; give each copy of
this template its own `name` in `.ddev/config.yaml`.

## Licence

MIT. See [LICENSE](LICENSE).
