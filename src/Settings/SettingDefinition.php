<?php

declare(strict_types=1);

namespace Filagora\Core\Settings;

/**
 * A setting a package owns: its key, type and default. The database only holds
 * values that differ from, or are not covered by, a definition.
 */
final readonly class SettingDefinition
{
    public function __construct(
        public string $key,
        public SettingType $type = SettingType::String,
        public mixed $default = null,
        public ?string $label = null,
        public ?string $description = null,
        public bool $public = false,
    ) {}
}
