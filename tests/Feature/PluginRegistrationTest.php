<?php

declare(strict_types=1);

use Filagora\Core\FilagoraCorePlugin;
use Filament\Panel;

it('merges the package config', function () {
    expect(config('filagora.table_prefix'))->toBe('filagora_');
});

it('registers on a panel', function () {
    $panel = Panel::make()->id('admin')->plugin(FilagoraCorePlugin::make());

    expect($panel->hasPlugin('filagora-core'))->toBeTrue();
});
