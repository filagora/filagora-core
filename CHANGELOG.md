# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [0.1.2] - 2026-10-11

### Changed

- **The Composer package is now `zcwilt/forumkit-core`** (it was `zencart/forumkit-core`), matching the GitHub owner. Change your `composer.json` requirement to `zcwilt/forumkit-core`; the code, the `ForumKit\Core\` namespace and the API are unchanged. The old `zencart/forumkit-core` package is abandoned.

## [0.1.1] - 2026-10-10

### Added

- Schema extensions: `SchemaExtension` and `SchemaRegistry::extend()` let a profile package declare extra columns, indexes and foreign keys that a site's tables carry beyond a package's own snapshot, plus the migrations that create them. `forumkit:adopt` checks the package snapshot and every registered extension exactly, and still reports anything undeclared as unexpected. Extensions for modules that are not installed are ignored; redefining a column the package owns is an error.
- `forumkit:schema:dump-extension`: writes only what a table has beyond a package's snapshot, in the form `SchemaExtension::fromFile()` loads.

### Changed

- `TableShape` now compares generated columns (kind and expression) and the index type (`FULLTEXT` versus `BTREE`). Snapshots written by `0.1.0` stay valid; new snapshots record both. Core's own snapshot was regenerated.
- `TableShape::inspect()` and the dump commands now fail with a clear message when the table does not exist, instead of describing an empty table.

## [0.1.0] - 2026-10-10

First release. The API may change between `0.x` minor versions.

### Added

- Table prefix helper (`Tables::name()`, default `forumkit_`), user model resolution (`ForumKit::useUserModel()`, `forumkit.user_model`, the auth provider) with a configurable title attribute, and `UserKey::column()` for user foreign keys.
- Swappable models through `forumkit.models` and `Models::class()`.
- Module registry (`ModuleRegistry`) and navigation group registry (`NavigationGroupRegistry`), with host overrides under `forumkit.navigation.groups`.
- System settings: `Setting` model and migration, typed `SettingsRegistry` with package-declared `SettingDefinition`s, the `SettingChanged` event, and a Filament `SettingResource` gated by the `forumkit.settings.manage` Gate ability (denied until it is defined).
- Safe markdown rendering (`MarkdownRenderer`, CommonMark and GitHub-flavoured, raw HTML escaped, unsafe links dropped, external links marked) with an `ExtensionRegistry` for packages.
- `PrivacyContributor` contract and `PrivacyContributorRegistry`.
- `forumkit:doctor` with an extensible set of `DoctorCheck`s, and `forumkit:install`.
- `forumkit:adopt`: start using a package on a database that already holds its tables. It checks every table against the package's shipped snapshot and, only if all match, records the package's migrations as run. `--dry-run` and `--package=` options; refuses with a list of differences on a mismatch or partial install; never alters tables or data.
- `forumkit:schema:dump`, which writes a package's table snapshot (`database/schema.php`).
