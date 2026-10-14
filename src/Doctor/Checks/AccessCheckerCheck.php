<?php

declare(strict_types=1);

namespace Filagora\Core\Doctor\Checks;

use Filagora\Core\Contracts\AccessChecker;
use Filagora\Core\Contracts\DoctorCheck;
use Filagora\Core\Doctor\CheckResult;
use Filagora\Core\Support\DenyAllAccessChecker;

final class AccessCheckerCheck implements DoctorCheck
{
    public function run(): CheckResult
    {
        /** @var AccessChecker $checker */
        $checker = app(AccessChecker::class);

        return $checker instanceof DenyAllAccessChecker
            ? CheckResult::warn('Access checker', 'Nothing is bound, so every staff screen denies everyone. Install filagora/filagora-entitlements or bind Filagora\Core\Contracts\AccessChecker.')
            : CheckResult::pass('Access checker', $checker::class);
    }
}
