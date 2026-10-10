<?php

declare(strict_types=1);

use Filament\Panel;
use ForumKit\Example\PackagePlugin;

it('merges the package config', function () {
    expect(config('forumkit-example.navigation_group'))->toBe('Example');
});

it('registers on a panel', function () {
    $panel = Panel::make()->id('admin')->plugin(PackagePlugin::make());

    expect($panel->hasPlugin('forumkit-example'))->toBeTrue();
});
