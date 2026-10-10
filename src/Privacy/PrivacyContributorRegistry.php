<?php

declare(strict_types=1);

namespace ForumKit\Core\Privacy;

use ForumKit\Core\Contracts\PrivacyContributor;

final class PrivacyContributorRegistry
{
    /** @var array<string, PrivacyContributor> */
    private array $contributors = [];

    /**
     * Registering a contributor with an existing key replaces it.
     */
    public function register(PrivacyContributor $contributor): void
    {
        $this->contributors[$contributor->key()] = $contributor;
    }

    /**
     * @return array<string, PrivacyContributor>
     */
    public function all(): array
    {
        return $this->contributors;
    }
}
