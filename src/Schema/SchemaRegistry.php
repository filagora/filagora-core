<?php

declare(strict_types=1);

namespace ForumKit\Core\Schema;

/**
 * The schema snapshots registered by installed packages.
 */
final class SchemaRegistry
{
    /** @var array<string, PackageSchema> */
    private array $schemas = [];

    public function register(PackageSchema $schema): void
    {
        $this->schemas[$schema->module] = $schema;
    }

    /**
     * @return array<string, PackageSchema>
     */
    public function all(): array
    {
        return $this->schemas;
    }

    public function get(string $module): ?PackageSchema
    {
        return $this->schemas[$module] ?? null;
    }
}
