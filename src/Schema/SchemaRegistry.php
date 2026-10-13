<?php

declare(strict_types=1);

namespace Filagora\Core\Schema;

/**
 * The schema snapshots registered by installed packages.
 */
final class SchemaRegistry
{
    /** @var array<string, PackageSchema> */
    private array $schemas = [];

    /** @var list<SchemaExtension> */
    private array $extensions = [];

    public function register(PackageSchema $schema): void
    {
        $this->schemas[$schema->module] = $schema;
    }

    /**
     * Declare extras another package's tables carry on a particular site. Ignored when the
     * extended module is not installed.
     */
    public function extend(SchemaExtension $extension): void
    {
        $this->extensions[] = $extension;
    }

    /**
     * @return list<SchemaExtension>
     */
    public function extensionsFor(string $module): array
    {
        return array_values(array_filter(
            $this->extensions,
            fn (SchemaExtension $extension): bool => $extension->module === $module,
        ));
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
