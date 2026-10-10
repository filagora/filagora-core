<?php

declare(strict_types=1);

namespace ForumKit\Core\Schema;

use ForumKit\Core\Support\Tables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a site that already has a package's tables (for example one moving from the code
 * the package was extracted from) start using the package without running its create
 * migrations: it checks every table against the package's snapshot and, only if all of
 * them match, records the package's migrations as run.
 *
 * It never creates, alters or drops a table and never touches table data.
 */
final class Adopter
{
    public function __construct(private readonly SchemaRegistry $registry) {}

    /**
     * @return list<AdoptionPlan>
     */
    public function plan(?string $module = null): array
    {
        $plans = [];

        foreach ($this->registry->all() as $schema) {
            if ($module === null || $schema->module === $module) {
                $plans[] = $this->planFor($schema);
            }
        }

        return $plans;
    }

    public function planFor(PackageSchema $schema): AdoptionPlan
    {
        $pending = $this->pendingMigrations($schema);
        $existing = array_values(array_filter(
            array_keys($schema->tables),
            fn (string $base): bool => Schema::hasTable(Tables::name($base)),
        ));

        if ($existing === []) {
            return new AdoptionPlan($schema->module, AdoptionStatus::Fresh, [], $pending);
        }

        if (count($existing) !== count($schema->tables)) {
            $absent = array_values(array_diff(array_keys($schema->tables), $existing));

            return new AdoptionPlan($schema->module, AdoptionStatus::Partial, array_map(
                fn (string $base): string => "{$base}: table is missing while other tables of this package exist",
                $absent,
            ), $pending);
        }

        $differences = [];

        foreach ($schema->tables as $base => $expected) {
            foreach (TableShape::inspect($base)->differencesFrom($expected) as $difference) {
                $differences[] = "{$base}: {$difference}";
            }
        }

        if ($differences !== []) {
            return new AdoptionPlan($schema->module, AdoptionStatus::Mismatch, $differences, $pending);
        }

        return new AdoptionPlan(
            $schema->module,
            $pending === [] ? AdoptionStatus::AlreadyAdopted : AdoptionStatus::Adoptable,
            [],
            $pending,
        );
    }

    /**
     * Record the plan's migrations as run, in one batch. Refuses anything that is not adoptable.
     */
    public function apply(AdoptionPlan $plan): void
    {
        if (! $plan->status->canApply()) {
            throw new \LogicException("Cannot adopt [{$plan->module}]: {$plan->status->value}.");
        }

        DB::transaction(function () use ($plan): void {
            $batch = ((int) DB::table('migrations')->max('batch')) + 1;

            foreach ($plan->pendingMigrations as $migration) {
                DB::table('migrations')->insert(['migration' => $migration, 'batch' => $batch]);
            }
        });
    }

    /**
     * @return list<string>
     */
    private function pendingMigrations(PackageSchema $schema): array
    {
        if (! Schema::hasTable('migrations')) {
            return $schema->migrationNames();
        }

        $recorded = DB::table('migrations')->pluck('migration')->all();

        return array_values(array_diff($schema->migrationNames(), $recorded));
    }
}
