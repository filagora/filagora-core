<?php

declare(strict_types=1);

namespace Filagora\Core\Contracts;

/**
 * A step that rewrites the HTML produced by the markdown renderer. Packages use
 * this for features CommonMark cannot express, for example resolving mentions.
 * The input is already safe HTML; a transformer must keep it safe.
 */
interface HtmlTransformer
{
    public function transform(string $html): string;
}
