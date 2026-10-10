<?php

declare(strict_types=1);

namespace ForumKit\Core\Schema;

enum AdoptionStatus: string
{
    /** Every table exists and matches; some migrations are not yet recorded. */
    case Adoptable = 'adoptable';

    /** Every migration is already recorded. Nothing to do. */
    case AlreadyAdopted = 'already adopted';

    /** None of the package's tables exist: run `php artisan migrate` instead. */
    case Fresh = 'fresh install';

    /** Some of the package's tables exist and others do not. */
    case Partial = 'partly present';

    /** The tables exist but differ from what the package expects. */
    case Mismatch = 'mismatch';

    public function canApply(): bool
    {
        return $this === self::Adoptable;
    }

    public function isRefusal(): bool
    {
        return $this === self::Partial || $this === self::Mismatch;
    }
}
