<?php

declare(strict_types=1);

namespace ForumKit\Core\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use ForumKit\Core\Settings\SettingType;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->required()
                    ->maxLength(120)
                    ->helperText(__('forumkit::messages.settings.key_help'))
                    ->unique(ignoreRecord: true),
                TextInput::make('label')
                    ->maxLength(160),
                Select::make('value_type')
                    ->required()
                    ->live()
                    ->default(SettingType::String->value)
                    ->options(self::typeOptions()),
                Toggle::make('is_public')
                    ->label(__('forumkit::messages.settings.public'))
                    ->default(false),
                TextInput::make('value')
                    ->visible(fn (Get $get): bool => in_array($get('value_type'), [SettingType::String->value, SettingType::Integer->value, SettingType::Page->value], true))
                    ->numeric(fn (Get $get): bool => $get('value_type') === SettingType::Integer->value)
                    ->maxLength(255),
                Select::make('value')
                    ->visible(fn (Get $get): bool => $get('value_type') === SettingType::Boolean->value)
                    ->required(fn (Get $get): bool => $get('value_type') === SettingType::Boolean->value)
                    ->options([
                        '1' => __('forumkit::messages.settings.true'),
                        '0' => __('forumkit::messages.settings.false'),
                    ]),
                Textarea::make('value')
                    ->columnSpanFull()
                    ->rows(5)
                    ->visible(fn (Get $get): bool => in_array($get('value_type'), [SettingType::Text->value, SettingType::Json->value], true))
                    ->rule(fn (Get $get): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                        if ($get('value_type') === SettingType::Json->value && $value !== null && $value !== '' && ! json_validate((string) $value)) {
                            $fail(__('forumkit::messages.settings.invalid_json'));
                        }
                    }),
                Textarea::make('description')
                    ->columnSpanFull()
                    ->rows(3),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function typeOptions(): array
    {
        $options = [];

        foreach (SettingType::cases() as $type) {
            $options[$type->value] = ucfirst($type->value);
        }

        return $options;
    }
}
