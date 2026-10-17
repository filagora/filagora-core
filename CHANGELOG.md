# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [0.2.2] - 2026-10-17

### Added

- A schema extension can declare an index the site replaced: `drop_indexes` names base indexes the site's table does not have, because it has a composite index in their place (the composite is declared under `indexes` as before). `filagora:schema:dump-extension` writes it, and `filagora:adopt` checks the table against the package's snapshot with those indexes removed. Naming an index the package does not have is an error.

## [0.2.1] - 2026-10-14

### Added

- **`AccessChecker`** contract (`allows`, `allowsAny`, `allowsAll`) and `DenyAllAccessChecker`, the default until something is bound, so a forgotten binding locks screens instead of opening them. `filagora:doctor` warns while the default is in use. Packages ask it instead of depending on `filagora-entitlements`.
- `GateAbilities::define()` and `::keys()` (a package's `gates` config as Gate abilities that ask the bound checker) and the Filament trait `Filagora\Core\Filament\Concerns\GatedByAbility` (moved here from `filagora-entitlements`, which keeps a deprecated alias).
- `RetentionRule` contract, `RetentionRuleRegistry` and `TableRetentionRule` (delete rows of a table older than N days, with a dry run) for the compliance package's prune command.

## [0.2.0] - 2026-10-13

### Changed

- **Renamed to Filagora.** The Composer package is now `filagora/filagora-core` (it was `zcwilt/forumkit-core`), and the repository moved to `github.com/filagora/filagora-core`. The PHP namespace is `Filagora\Core\` (was `ForumKit\Core\`), the config file is `config/filagora.php`, environment variables are `FILAGORA_*`, the default table prefix is `filagora_`, and the commands are `filagora:install`, `filagora:doctor`, `filagora:adopt` and `filagora:schema:dump`. Nothing else changed: this is a rename, so moving from 0.1 means renaming those names in your application and its `.env`.


## [0.1.3] - 2026-10-12

### Fixed

- **Settings definitions were lost between jobs in a long-lived queue worker.** `SettingsRegistry` is bound per request scope (so its value memo does not leak between requests), and the definitions packages declare at boot lived in it, so a worker lost every declared default after its first job. Definitions now live in a separate singleton (`SettingDefinitions`); the registry's API is unchanged.

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
