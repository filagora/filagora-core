<?php

declare(strict_types=1);

namespace ForumKit\Core\Contracts;

use ForumKit\Core\Doctor\CheckResult;

/**
 * One health check run by `forumkit:doctor`. A check must not throw: report a
 * problem as a failing result.
 */
interface DoctorCheck
{
    public function run(): CheckResult;
}
