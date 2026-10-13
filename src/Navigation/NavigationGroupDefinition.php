<?php

declare(strict_types=1);

namespace Filagora\Core\Navigation;

use Closure;

final readonly class NavigationGroupDefinition
{
    /**
     * @param  string|Closure(): string  $label  A closure keeps translation lazy.
     */
    public function __construct(
        public string $key,
        public string|Closure $label,
        public ?string $icon = null,
        public int $sort = 100,
        public bool $collapsed = true,
    ) {}
}
