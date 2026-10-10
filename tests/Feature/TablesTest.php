<?php

declare(strict_types=1);

use ForumKit\Core\Support\Tables;

it('prefixes base names with forumkit_ by default', function () {
    expect(Tables::name('system_settings'))->toBe('forumkit_system_settings');
});

it('follows the configured prefix', function () {
    config(['forumkit.table_prefix' => 'fk_']);

    expect(Tables::name('forums'))->toBe('fk_forums');
});

it('uses the bare base name when the prefix is empty', function () {
    config(['forumkit.table_prefix' => '']);

    expect(Tables::name('threads'))->toBe('threads');
});
