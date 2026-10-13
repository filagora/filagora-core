<?php

declare(strict_types=1);

namespace Filagora\Core\Schema;

final readonly class AdoptionPlan
{
    /**
     * @param  list<string>  $differences  Problems found, each prefixed with the table name.
     * @param  list<string>  $pendingMigrations  Migration names that would be recorded as run.
     */
    public function __construct(
        public string $module,
        public AdoptionStatus $status,
        public array $differences,
        public array $pendingMigrations,
    ) {}
}
