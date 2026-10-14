<?php

declare(strict_types=1);

namespace Filagora\Core\Support;

use Filagora\Core\Contracts\AccessChecker;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * The checker used until a package or the host binds a real one: nobody may do anything.
 * `filagora:doctor` warns when this is the one in use.
 */
final class DenyAllAccessChecker implements AccessChecker
{
    public function allows(?Authenticatable $user, string $key): bool
    {
        return false;
    }

    public function allowsAny(?Authenticatable $user, array $keys): bool
    {
        return false;
    }

    public function allowsAll(?Authenticatable $user, array $keys): bool
    {
        return false;
    }
}
