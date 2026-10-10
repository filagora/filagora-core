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
    | Navigation group
    |--------------------------------------------------------------------------
    |
    | The Filament navigation group this package's resources are placed in.
    | `env()` is only ever used in config files, and config holds no closures.
    |
    */
    'navigation_group' => env('FORUMKIT_NAVIGATION_GROUP', 'ForumKit'),

];
