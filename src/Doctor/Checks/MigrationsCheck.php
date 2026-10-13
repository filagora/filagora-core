<?php

declare(strict_types=1);

namespace Filagora\Core\Doctor\Checks;

use Filagora\Core\Contracts\DoctorCheck;
use Filagora\Core\Doctor\CheckResult;
use Filagora\Core\Filagora;
use Filagora\Core\Support\Tables;
use Illuminate\Support\Facades\Schema;

final class MigrationsCheck implements DoctorCheck
{
    public function run(): CheckResult
    {
        $table = Tables::name('system_settings');

        if (! Schema::hasTable($table)) {
            return CheckResult::fail('Migrations', "Table [{$table}] is missing. Run `php artisan migrate`.");
        }

        $prefix = Filagora::tablePrefix();

        return CheckResult::pass('Migrations', $prefix === '' ? 'Core tables present (no table prefix)' : "Core tables present (prefix {$prefix})");
    }
}
