<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Table prefix
    |--------------------------------------------------------------------------
    |
    | Prepended to every table the ForumKit packages create, so a public install
    | never collides with a host's own tables. Set it to an empty string to adopt
    | existing tables that already use the base names. Set it before running the
    | migrations: changing it afterwards does not rename existing tables.
    |
    */
    'table_prefix' => env('FORUMKIT_TABLE_PREFIX', 'forumkit_'),

    /*
    |--------------------------------------------------------------------------
    | User model
    |--------------------------------------------------------------------------
    |
    | The host's user model class. When null, the model of the default auth
    | provider (`auth.providers.users.model`) is used. ForumKit never alters the
    | host's users table; relations are attached to this model.
    |
    */
    'user_model' => env('FORUMKIT_USER_MODEL'),

    /*
    |--------------------------------------------------------------------------
    | User title attribute
    |--------------------------------------------------------------------------
    |
    | The attribute on the user model that names a user in selects, tables and
    | labels, for example `username` or `name`.
    |
    */
    'user_title_attribute' => env('FORUMKIT_USER_TITLE_ATTRIBUTE', 'name'),

    /*
    |--------------------------------------------------------------------------
    | Swappable models
    |--------------------------------------------------------------------------
    |
    | Point a key at your own subclass to replace a ForumKit model. Leave a key out
    | to use the package default.
    |
    */
    'models' => [
        // 'setting' => App\Models\Setting::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown
    |--------------------------------------------------------------------------
    |
    | `line_breaks` turns a single newline into a visible break. `internal_hosts`
    | lists extra hosts whose links are not marked as external; the host of
    | `app.url` is always included.
    |
    */
    'markdown' => [
        'line_breaks' => true,
        'max_nesting_level' => 100,
        'internal_hosts' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | `ability` is the Gate ability that grants access to the settings screen.
    | Nothing defines it by default, so access is denied until it is defined.
    |
    */
    'settings' => [
        'ability' => 'forumkit.settings.manage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    |
    | Each package registers its own sidebar groups. List only the overrides you
    | want, keyed by group key, for example:
    |
    |   'groups' => ['system' => ['label' => 'Admin', 'sort' => 900, 'collapsed' => false]],
    |
    | Supported keys: label, icon, sort, collapsed.
    |
    */
    'navigation' => [
        'groups' => [],
    ],

];
