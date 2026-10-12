<?php

declare(strict_types=1);

namespace ForumKit\Core\Settings;

use ForumKit\Core\Models\Setting;
use ForumKit\Core\Support\Models;
use Illuminate\Support\Facades\Schema;

/**
 * Settings declared by the installed packages, and typed access to their values.
 *
 * Resolution order: a stored row, then the definition's default, then the
 * caller's fallback. Values are memoised for the life of the registry and
 * dropped when a setting changes. Register as `scoped`, so long-lived workers
 * never carry values between requests; the definitions live in a separate singleton
 * (`SettingDefinitions`) so they survive that reset.
 */
final class SettingsRegistry
{
    /** @var array<string, mixed> */
    private array $memo = [];

    public function __construct(private readonly SettingDefinitions $definitions) {}

    public function define(SettingDefinition $definition): void
    {
        $this->definitions->define($definition);
    }

    public function definition(string $key): ?SettingDefinition
    {
        return $this->definitions->get($key);
    }

    /**
     * @return array<string, SettingDefinition>
     */
    public function definitions(): array
    {
        return $this->definitions->all();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $key = trim($key);

        if ($key === '') {
            return $default;
        }

        if (! array_key_exists($key, $this->memo)) {
            $this->memo[$key] = $this->resolve($key);
        }

        return $this->memo[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key);

        return is_scalar($value) ? (string) $value : $default;
    }

    public function integer(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        return is_bool($value) ? $value : $default;
    }

    /**
     * Store a value for a key, creating the row when needed. The type comes from
     * the definition, or the existing row, or defaults to string.
     */
    public function set(string $key, mixed $value): void
    {
        $model = Models::class('setting');
        $definition = $this->definition($key);

        /** @var Setting $setting */
        $setting = $model::query()->firstOrNew(['key' => $key]);
        $type = $definition !== null
            ? $definition->type
            : ($setting->exists ? $setting->value_type : SettingType::String);

        $setting->fill([
            'value_type' => $type,
            'value' => $type->serialize($value),
            'label' => $setting->label ?? $definition?->label,
            'description' => $setting->description ?? $definition?->description,
            'is_public' => $setting->exists ? $setting->is_public : ($definition !== null && $definition->public),
        ])->save();
    }

    /**
     * Drop memoised values. Called when a setting changes.
     */
    public function flush(): void
    {
        $this->memo = [];
    }

    private function resolve(string $key): mixed
    {
        $row = $this->row($key);

        if ($row !== null) {
            return $row->typedValue();
        }

        return $this->definition($key)?->default;
    }

    private function row(string $key): ?Setting
    {
        $model = Models::class('setting');

        if (! Schema::hasTable((new $model)->getTable())) {
            return null;
        }

        /** @var Setting|null */
        return $model::query()->where('key', $key)->first();
    }
}
