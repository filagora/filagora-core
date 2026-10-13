<?php

declare(strict_types=1);

namespace Filagora\Core\Models;

use Filagora\Core\Events\SettingChanged;
use Filagora\Core\Settings\SettingType;
use Filagora\Core\Support\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string|null $label
 * @property string|null $value
 * @property SettingType $value_type
 * @property bool $is_public
 * @property string|null $description
 */
class Setting extends Model
{
    protected $fillable = [
        'key',
        'label',
        'value',
        'value_type',
        'is_public',
        'description',
    ];

    public function getTable(): string
    {
        return Tables::name('system_settings');
    }

    protected function casts(): array
    {
        return [
            'value_type' => SettingType::class,
            'is_public' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (self $setting) => SettingChanged::dispatch($setting->key));
        static::deleted(fn (self $setting) => SettingChanged::dispatch($setting->key));
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * The value cast to its declared type.
     */
    public function typedValue(): mixed
    {
        return $this->value_type->cast($this->value);
    }
}
