<?php

declare(strict_types=1);

namespace ForumKit\Core\Settings;

/**
 * The settings packages have declared. Bound as a singleton: definitions are registered once,
 * when the application boots, and must outlive the per-request scope the value memo lives in,
 * or a queue worker would lose them after its first job.
 */
final class SettingDefinitions
{
    /** @var array<string, SettingDefinition> */
    private array $definitions = [];

    public function define(SettingDefinition $definition): void
    {
        $this->definitions[$definition->key] = $definition;
    }

    public function get(string $key): ?SettingDefinition
    {
        return $this->definitions[$key] ?? null;
    }

    /**
     * @return array<string, SettingDefinition>
     */
    public function all(): array
    {
        return $this->definitions;
    }
}
