<?php

declare(strict_types=1);

namespace ForumKit\Core\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Implemented by a package that stores personal data about users, so the
 * compliance package can export and erase it without knowing the package.
 */
interface PrivacyContributor
{
    /**
     * Stable key naming this contributor's section in an export, for example `forums`.
     */
    public function key(): string;

    /**
     * Everything this package holds about the user, as JSON-serialisable data.
     *
     * @return array<string, mixed>
     */
    public function export(Model $user): array;

    /**
     * Erase or anonymise the user's data held by this package.
     */
    public function erase(Model $user): void;
}
