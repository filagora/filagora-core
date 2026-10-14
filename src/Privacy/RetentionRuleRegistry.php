<?php

declare(strict_types=1);

namespace Filagora\Core\Privacy;

use Filagora\Core\Contracts\RetentionRule;

final class RetentionRuleRegistry
{
    /** @var array<string, RetentionRule> */
    private array $rules = [];

    /**
     * Registering a rule with an existing key replaces it.
     */
    public function register(RetentionRule $rule): void
    {
        $this->rules[$rule->key()] = $rule;
    }

    /**
     * @return array<string, RetentionRule>
     */
    public function all(): array
    {
        return $this->rules;
    }
}
