<?php

declare(strict_types=1);

namespace Filagora\Core\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched after a setting row is saved or deleted.
 */
final class SettingChanged
{
    use Dispatchable;

    public function __construct(public readonly string $key) {}
}
