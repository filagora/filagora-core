<?php

declare(strict_types=1);

namespace ForumKit\Core\Modules;

use ForumKit\Core\Exceptions\InvalidConfiguration;

/**
 * The modules (ForumKit packages) present in this install. Each package registers
 * itself from its service provider, so hosts and other packages can ask what is
 * installed and `forumkit:doctor` can verify the dependency graph.
 */
final class ModuleRegistry
{
    /** @var array<string, Module> */
    private array $modules = [];

    public function register(Module $module): void
    {
        $this->modules[$module->id] = $module;
    }

    public function has(string $id): bool
    {
        return isset($this->modules[$id]);
    }

    public function get(string $id): Module
    {
        return $this->modules[$id] ?? throw InvalidConfiguration::moduleNotRegistered($id);
    }

    /**
     * @return array<string, Module>
     */
    public function all(): array
    {
        return $this->modules;
    }

    /**
     * Required modules that are not registered, keyed by the module that needs them.
     *
     * @return array<string, list<string>>
     */
    public function missingRequirements(): array
    {
        $missing = [];

        foreach ($this->modules as $module) {
            $absent = array_values(array_filter(
                $module->requires,
                fn (string $id): bool => ! $this->has($id),
            ));

            if ($absent !== []) {
                $missing[$module->id] = $absent;
            }
        }

        return $missing;
    }
}
