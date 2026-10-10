<?php

declare(strict_types=1);

namespace ForumKit\Core\Tests\Support;

use Filament\Panel;
use Filament\PanelProvider;
use ForumKit\Core\ForumKitCorePlugin;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->plugin(ForumKitCorePlugin::make());
    }
}
