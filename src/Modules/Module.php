<?php

declare(strict_types=1);

namespace Filagora\Core\Modules;

/**
 * One installed Filagora package, as declared by its service provider.
 */
final readonly class Module
{
    /**
     * @param  string  $id  Short unique id, for example `entitlements`.
     * @param  string  $package  Composer package name, for example `filagora/filagora-entitlements`.
     * @param  list<string>  $requires  Ids of modules this one needs.
     */
    public function __construct(
        public string $id,
        public string $label,
        public string $package,
        public array $requires = [],
    ) {}
}
