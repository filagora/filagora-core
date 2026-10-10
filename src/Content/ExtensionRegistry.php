<?php

declare(strict_types=1);

namespace ForumKit\Core\Content;

use ForumKit\Core\Contracts\HtmlTransformer;
use League\CommonMark\Extension\ExtensionInterface;

/**
 * How packages extend markdown rendering: CommonMark extensions for syntax, and
 * HTML transformers that run on the rendered output in priority order.
 */
final class ExtensionRegistry
{
    /** @var list<ExtensionInterface> */
    private array $extensions = [];

    /** @var list<array{priority: int, transformer: HtmlTransformer}> */
    private array $transformers = [];

    private int $revision = 0;

    public function extension(ExtensionInterface $extension): void
    {
        $this->extensions[] = $extension;
        $this->revision++;
    }

    /**
     * Lower priorities run first; equal priorities keep registration order.
     */
    public function transformer(HtmlTransformer $transformer, int $priority = 100): void
    {
        $this->transformers[] = ['priority' => $priority, 'transformer' => $transformer];
        $this->revision++;
    }

    /**
     * @return list<ExtensionInterface>
     */
    public function extensions(): array
    {
        return $this->extensions;
    }

    /**
     * @return list<HtmlTransformer>
     */
    public function transformers(): array
    {
        $indexed = $this->transformers;

        usort($indexed, fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);

        return array_map(fn (array $entry): HtmlTransformer => $entry['transformer'], $indexed);
    }

    /**
     * Changes whenever something is registered, so the renderer knows to rebuild.
     */
    public function revision(): int
    {
        return $this->revision;
    }
}
