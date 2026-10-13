<?php

declare(strict_types=1);

namespace Filagora\Core\Doctor\Checks;

use Filagora\Core\Contracts\DoctorCheck;
use Filagora\Core\Doctor\CheckResult;
use Filagora\Core\Exceptions\InvalidConfiguration;
use Filagora\Core\Support\UserModel;
use Illuminate\Support\Facades\Schema;

final class UserModelCheck implements DoctorCheck
{
    public function run(): CheckResult
    {
        try {
            $class = UserModel::class();
            $table = UserModel::table();
        } catch (InvalidConfiguration $e) {
            return CheckResult::fail('User model', $e->getMessage());
        }

        return Schema::hasTable($table)
            ? CheckResult::pass('User model', "{$class} (table {$table})")
            : CheckResult::fail('User model', "{$class} resolves, but its table [{$table}] does not exist.");
    }
}
