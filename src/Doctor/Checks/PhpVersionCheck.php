<?php

declare(strict_types=1);

namespace Filagora\Core\Doctor\Checks;

use Filagora\Core\Contracts\DoctorCheck;
use Filagora\Core\Doctor\CheckResult;

final class PhpVersionCheck implements DoctorCheck
{
    public function run(): CheckResult
    {
        return version_compare(PHP_VERSION, '8.4.0', '>=')
            ? CheckResult::pass('PHP', 'PHP '.PHP_VERSION)
            : CheckResult::fail('PHP', 'PHP 8.4 or newer is required, found '.PHP_VERSION);
    }
}
