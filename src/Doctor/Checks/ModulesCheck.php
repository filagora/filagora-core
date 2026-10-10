<?php

declare(strict_types=1);

namespace ForumKit\Core\Doctor\Checks;

use ForumKit\Core\Contracts\DoctorCheck;
use ForumKit\Core\Doctor\CheckResult;
use ForumKit\Core\Modules\ModuleRegistry;

final class ModulesCheck implements DoctorCheck
{
    public function __construct(private readonly ModuleRegistry $modules) {}

    public function run(): CheckResult
    {
        $missing = $this->modules->missingRequirements();

        if ($missing === []) {
            return CheckResult::pass('Modules', implode(', ', array_keys($this->modules->all())));
        }

        $lines = [];

        foreach ($missing as $module => $absent) {
            $lines[] = "{$module} needs ".implode(', ', $absent);
        }

        return CheckResult::fail('Modules', 'Missing required modules: '.implode('; ', $lines));
    }
}
