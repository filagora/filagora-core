<?php

declare(strict_types=1);

namespace Filagora\Core\Contracts;

/**
 * Implemented by a package that keeps records which should not be kept forever (moderation
 * logs, email logs, old requests), so the compliance package can prune them all with one
 * command without knowing the package.
 */
interface RetentionRule
{
    /**
     * Stable key naming the rule in the prune report, for example `forums.moderation_logs`.
     */
    public function key(): string;

    /**
     * Delete what is past its retention period and return how many records that was. With
     * `$dryRun` delete nothing and return how many would be.
     */
    public function prune(bool $dryRun): int;
}
