<?php

declare(strict_types=1);

namespace Filagora\Core\Doctor;

use Filagora\Core\Contracts\DoctorCheck;
use Throwable;

/**
 * The checks run by `filagora:doctor`. Core registers its own; every other
 * package registers the checks for what it needs.
 */
final class Doctor
{
    /** @var list<DoctorCheck> */
    private array $checks = [];

    public function register(DoctorCheck $check): void
    {
        $this->checks[] = $check;
    }

    /**
     * @return list<CheckResult>
     */
    public function run(): array
    {
        return array_map(function (DoctorCheck $check): CheckResult {
            try {
                return $check->run();
            } catch (Throwable $e) {
                return CheckResult::fail($check::class, 'Check threw: '.$e->getMessage());
            }
        }, $this->checks);
    }

    /**
     * @param  list<CheckResult>  $results
     */
    public static function passed(array $results): bool
    {
        foreach ($results as $result) {
            if ($result->status === CheckStatus::Fail) {
                return false;
            }
        }

        return true;
    }
}
