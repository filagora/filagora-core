# ForumKit Core

The foundation of the ForumKit packages: a Laravel 13 / Filament 5 plugin providing the
module and navigation registries, user-model and table-prefix resolution, system settings,
markdown rendering and the `forumkit:doctor` and `forumkit:install` commands.

> Work in progress: `0.x`, nothing released yet.

## Install

```bash
composer require zencart/forumkit-core
php artisan vendor:publish --tag=forumkit-config
```

Register the plugin on a Filament panel:

```php
->plugin(\ForumKit\Core\ForumKitCorePlugin::make())
```

## Configuration

`config/forumkit.php` is merged with the host's copy, so list only what
differs. Environment variables are prefixed `FORUMKIT_`.

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
