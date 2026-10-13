<?php

declare(strict_types=1);

namespace Filagora\Core\Navigation;

use Filament\Navigation\NavigationGroup;

/**
 * Sidebar groups declared by the installed packages. A host can override a group's
 * label, icon, sort or collapsed state under `filagora.navigation.groups.<key>`.
 *
 * Resources place themselves in a group by returning `label($key)` from
 * `getNavigationGroup()`.
 */
final class NavigationGroupRegistry
{
    /** @var array<string, NavigationGroupDefinition> */
    private array $groups = [];

    /**
     * Register a group. Registering the same key again replaces it.
     */
    public function register(NavigationGroupDefinition $group): void
    {
        $this->groups[$group->key] = $group;
    }

    public function has(string $key): bool
    {
        return isset($this->groups[$key]);
    }

    /**
     * The resolved label for a group, with host overrides applied.
     */
    public function label(string $key): string
    {
        $definition = $this->groups[$key] ?? null;
        $override = config("filagora.navigation.groups.{$key}.label");

        if (is_string($override) && $override !== '') {
            return $override;
        }

        if ($definition === null) {
            return $key;
        }

        return is_string($definition->label) ? $definition->label : ($definition->label)();
    }

    /**
     * Group keys in sidebar order (sort ascending, then registration order).
     *
     * @return list<string>
     */
    public function keys(): array
    {
        $order = array_keys($this->groups);

        usort($order, fn (string $a, string $b): int => [$this->sortOf($a), array_search($a, array_keys($this->groups), true)]
            <=> [$this->sortOf($b), array_search($b, array_keys($this->groups), true)]);

        return $order;
    }

    /**
     * The groups as Filament objects, for `Panel::navigationGroups()`.
     *
     * @return list<NavigationGroup>
     */
    public function toFilament(): array
    {
        return array_map(function (string $key): NavigationGroup {
            $icon = config("filagora.navigation.groups.{$key}.icon", $this->groups[$key]->icon);
            $collapsed = config("filagora.navigation.groups.{$key}.collapsed", $this->groups[$key]->collapsed);

            return NavigationGroup::make($this->label($key))
                ->icon(is_string($icon) ? $icon : null)
                ->collapsed((bool) $collapsed);
        }, $this->keys());
    }

    private function sortOf(string $key): int
    {
        $override = config("filagora.navigation.groups.{$key}.sort");

        return is_int($override) ? $override : $this->groups[$key]->sort;
    }
}
