<?php

declare(strict_types=1);

use Filagora\Core\FilagoraCorePlugin;
use Filagora\Core\Navigation\NavigationGroupDefinition;
use Filagora\Core\Navigation\NavigationGroupRegistry;
use Filament\Panel;

function groups(): NavigationGroupRegistry
{
    $registry = new NavigationGroupRegistry;
    $registry->register(new NavigationGroupDefinition('system', 'System', sort: 900));
    $registry->register(new NavigationGroupDefinition('forums', fn (): string => 'Forums', icon: 'heroicon-o-chat-bubble-left-right', sort: 10));
    $registry->register(new NavigationGroupDefinition('logs', 'Logs', sort: 900));

    return $registry;
}

it('orders groups by sort, then by registration', function () {
    expect(groups()->keys())->toBe(['forums', 'system', 'logs']);
});

it('resolves lazy and plain labels', function () {
    expect(groups()->label('forums'))->toBe('Forums')
        ->and(groups()->label('system'))->toBe('System');
});

it('lets the host override label, sort, icon and collapsed state', function () {
    config(['filagora.navigation.groups' => [
        'system' => ['label' => 'Admin', 'sort' => 1, 'icon' => 'heroicon-o-cog', 'collapsed' => false],
    ]]);

    $registry = groups();
    $filament = $registry->toFilament();

    expect($registry->keys()[0])->toBe('system')
        ->and($filament[0]->getLabel())->toBe('Admin')
        ->and($filament[0]->getIcon())->toBe('heroicon-o-cog')
        ->and($filament[0]->isCollapsed())->toBeFalse();
});

it('registers the core system group', function () {
    $registry = app(NavigationGroupRegistry::class);

    expect($registry->has('system'))->toBeTrue()
        ->and($registry->label('system'))->toBe('System');
});

it('applies registered groups to a panel when the plugin boots', function () {
    $panel = Panel::make()->id('admin')->plugin(FilagoraCorePlugin::make());

    $panel->boot();

    expect(collect($panel->getNavigationGroups())->map->getLabel()->values()->all())->toContain('System');
});
