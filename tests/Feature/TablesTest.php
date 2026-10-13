<?php

declare(strict_types=1);

use Filagora\Core\Support\Tables;

it('prefixes base names with filagora_ by default', function () {
    expect(Tables::name('system_settings'))->toBe('filagora_system_settings');
});

it('follows the configured prefix', function () {
    config(['filagora.table_prefix' => 'fk_']);

    expect(Tables::name('forums'))->toBe('fk_forums');
});

it('uses the bare base name when the prefix is empty', function () {
    config(['filagora.table_prefix' => '']);

    expect(Tables::name('threads'))->toBe('threads');
});
