<?php

declare(strict_types=1);

namespace Filagora\Core\Privacy;

use Closure;
use Filagora\Core\Contracts\RetentionRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The common retention rule: delete the rows of a table whose date column is older than a number
 * of days. The number is read each time it runs (from a setting or config), and zero or less
 * keeps everything.
 */
final class TableRetentionRule implements RetentionRule
{
    /**
     * @param  Closure(): int  $days
     */
    public function __construct(
        private readonly string $key,
        private readonly string $table,
        private readonly string $column,
        private readonly Closure $days,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function prune(bool $dryRun): int
    {
        $days = ($this->days)();

        if ($days <= 0 || ! Schema::hasTable($this->table)) {
            return 0;
        }

        $query = DB::table($this->table)->where($this->column, '<', now()->subDays($days));
        $count = $query->count();

        if ($count > 0 && ! $dryRun) {
            $query->delete();
        }

        return $count;
    }
}
