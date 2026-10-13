<?php

declare(strict_types=1);

namespace Filagora\Core\Settings;

enum SettingType: string
{
    case String = 'string';
    case Text = 'text';
    case Integer = 'integer';
    case Boolean = 'boolean';
    case Json = 'json';
    /** A route beginning with `/`, or a CMS page slug. Stored and read as a string. */
    case Page = 'page';

    /**
     * Convert a stored string into this type. Null stays null.
     */
    public function cast(?string $stored): mixed
    {
        if ($stored === null) {
            return null;
        }

        return match ($this) {
            self::String, self::Text, self::Page => $stored,
            self::Integer => is_numeric($stored) ? (int) $stored : null,
            self::Boolean => filter_var($stored, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            self::Json => json_decode($stored, true),
        };
    }

    /**
     * Convert a value to its stored string form.
     */
    public function serialize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($this) {
            self::Boolean => $value ? '1' : '0',
            self::Json => json_encode($value, JSON_THROW_ON_ERROR),
            default => (string) $value,
        };
    }
}
