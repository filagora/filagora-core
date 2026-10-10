<?php

declare(strict_types=1);

use Filament\Panel;
use ForumKit\Core\ForumKitCorePlugin;

it('merges the package config', function () {
    expect(config('forumkit.navigation_group'))->toBe('ForumKit');
});

it('registers on a panel', function () {
    $panel = Panel::make()->id('admin')->plugin(ForumKitCorePlugin::make());

    expect($panel->hasPlugin('forumkit-core'))->toBeTrue();
});
