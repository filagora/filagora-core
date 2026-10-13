<?php

declare(strict_types=1);

namespace Filagora\Core\Commands;

use Filagora\Core\Schema\Adopter;
use Filagora\Core\Schema\AdoptionStatus;
use Illuminate\Console\Command;

class AdoptCommand extends Command
{
    protected $signature = 'filagora:adopt
        {--package= : Only this module (for example entitlements)}
        {--dry-run : Report what would happen without recording anything}';

    protected $description = 'Start using Filagora packages on a database that already holds their tables';

    public function handle(Adopter $adopter): int
    {
        $module = $this->option('package');
        $plans = $adopter->plan(is_string($module) && $module !== '' ? $module : null);

        if ($plans === []) {
            $this->warn('No packages with a schema snapshot are installed.');

            return self::SUCCESS;
        }

        $refused = false;

        foreach ($plans as $plan) {
            $this->line(sprintf('<info>%s</info>: %s', $plan->module, $plan->status->value));

            foreach ($plan->differences as $difference) {
                $this->line("  - {$difference}");
            }

            if ($plan->status->isRefusal()) {
                $refused = true;
            }

            if ($plan->status === AdoptionStatus::Fresh) {
                $this->line('  none of its tables exist; run `php artisan migrate`');
            }

            if ($plan->status->canApply()) {
                $this->line('  would record '.count($plan->pendingMigrations).' migration(s) as run');
            }
        }

        if ($refused) {
            $this->error('Nothing was recorded: fix the differences above, then run this again.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run: nothing was recorded.');

            return self::SUCCESS;
        }

        $recorded = 0;

        foreach ($plans as $plan) {
            if ($plan->status->canApply()) {
                $adopter->apply($plan);
                $recorded += count($plan->pendingMigrations);
            }
        }

        $this->info("Recorded {$recorded} migration(s) as run.");

        return self::SUCCESS;
    }
}
