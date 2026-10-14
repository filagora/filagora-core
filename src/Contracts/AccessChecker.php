<?php

declare(strict_types=1);

namespace Filagora\Core\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Answers "may this user do this?" for every Filagora package. A package asks with a key (a plain
 * string such as `filagora.forums.moderate`, set in its `gates` config) and never knows how the
 * answer is reached: `filagora-entitlements` answers from entitlement grants, a host can bind its
 * own (roles, policies, a permissions package) to use that instead.
 *
 * With nothing bound, `DenyAllAccessChecker` answers no to everyone, so a forgotten binding
 * locks screens instead of opening them. A guest (null user) is allowed nothing.
 */
interface AccessChecker
{
    public function allows(?Authenticatable $user, string $key): bool;

    /**
     * @param  list<string>  $keys
     */
    public function allowsAny(?Authenticatable $user, array $keys): bool;

    /**
     * @param  list<string>  $keys
     */
    public function allowsAll(?Authenticatable $user, array $keys): bool;
}
