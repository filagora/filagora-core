# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

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
