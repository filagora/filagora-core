<?php

declare(strict_types=1);

namespace Filagora\Core\Filament\Concerns;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Gates every action of a Filament resource behind one Laravel Gate ability. Nothing is
 * allowed unless the ability is defined and passes, so access is denied by default.
 */
trait GatedByAbility
{
    /**
     * The ability required for an action, or null to deny the action outright.
     */
    abstract protected static function abilityFor(string|UnitEnum $action, ?Model $record): ?string;

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $ability = static::abilityFor($action, $record);

        return $ability !== null && Gate::allows($ability)
            ? Response::allow()
            : Response::deny();
    }
}
