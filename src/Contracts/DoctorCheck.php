<?php

declare(strict_types=1);

namespace Filagora\Core\Contracts;

use Filagora\Core\Doctor\CheckResult;

/**
 * One health check run by `filagora:doctor`. A check must not throw: report a
 * problem as a failing result.
 */
interface DoctorCheck
{
    public function run(): CheckResult;
}
