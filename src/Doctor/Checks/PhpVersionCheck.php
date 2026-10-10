<?php

declare(strict_types=1);

namespace ForumKit\Core\Doctor\Checks;

use ForumKit\Core\Contracts\DoctorCheck;
use ForumKit\Core\Doctor\CheckResult;

final class PhpVersionCheck implements DoctorCheck
{
    public function run(): CheckResult
    {
        return version_compare(PHP_VERSION, '8.4.0', '>=')
            ? CheckResult::pass('PHP', 'PHP '.PHP_VERSION)
            : CheckResult::fail('PHP', 'PHP 8.4 or newer is required, found '.PHP_VERSION);
    }
}
