<?php

declare(strict_types=1);

namespace ForumKit\Core\Filament\Resources\Settings\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use ForumKit\Core\Filament\Resources\Settings\SettingResource;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
