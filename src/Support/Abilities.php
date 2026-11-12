<?php

declare(strict_types=1);

namespace Filagora\Core\Support;

/**
 * The Gate abilities core defines, shared by every package. Each maps to a key in the `gates` of
 * `config/filagora.php`, which a host can point at its own keys.
 */
final class Abilities
{
    /**
     * Moderate content on the host's own pages (the forums, and any other package with
     * moderation) without opening any dashboard screen. A package's dashboard screens keep
     * their own ability, so holding this alone never shows the dashboard anything.
     */
    public const MODERATE_FRONTEND = 'filagora.moderate_frontend';
}
