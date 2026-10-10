<?php

declare(strict_types=1);

use ForumKit\Core\Exceptions\InvalidConfiguration;
use ForumKit\Core\Modules\Module;
use ForumKit\Core\Modules\ModuleRegistry;

it('registers core itself', function () {
    $registry = app(ModuleRegistry::class);

    expect($registry->has('core'))->toBeTrue()
        ->and($registry->get('core')->package)->toBe('zencart/forumkit-core');
});

it('is a singleton', function () {
    expect(app(ModuleRegistry::class))->toBe(app(ModuleRegistry::class));
});

it('reports requirements that are not installed', function () {
    $registry = new ModuleRegistry;
    $registry->register(new Module('core', 'Core', 'zencart/forumkit-core'));
    $registry->register(new Module('forums', 'Forums', 'zencart/forumkit-forums', requires: ['core', 'entitlements']));

    expect($registry->missingRequirements())->toBe(['forums' => ['entitlements']]);

    $registry->register(new Module('entitlements', 'Entitlements', 'zencart/forumkit-entitlements', requires: ['core']));

    expect($registry->missingRequirements())->toBe([]);
});

it('throws for an unknown module', function () {
    (new ModuleRegistry)->get('nope');
})->throws(InvalidConfiguration::class, 'not registered');
