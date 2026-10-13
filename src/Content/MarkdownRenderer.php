<?php

declare(strict_types=1);

namespace Filagora\Core\Content;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Renders user-authored markdown to safe HTML: CommonMark plus GitHub-flavoured
 * extensions, raw HTML escaped, unsafe link schemes dropped, and external links
 * marked. Packages extend it through the ExtensionRegistry.
 *
 * Options come from `filagora.markdown`: `line_breaks` (a single newline becomes
 * a visible break), `max_nesting_level` and `internal_hosts` (hosts treated as
 * internal; the app URL's host is always included).
 */
final class MarkdownRenderer
{
    private ?MarkdownConverter $converter = null;

    private int $builtAtRevision = -1;

    public function __construct(private readonly ExtensionRegistry $registry) {}

    public function render(?string $markdown): string
    {
        $markdown = (string) $markdown;

        if ($markdown === '') {
            return '';
        }

        $html = $this->converter()->convert($markdown)->getContent();

        foreach ($this->registry->transformers() as $transformer) {
            $html = $transformer->transform($html);
        }

        return $html;
    }

    private function converter(): MarkdownConverter
    {
        if ($this->converter === null || $this->builtAtRevision !== $this->registry->revision()) {
            $this->converter = $this->build();
            $this->builtAtRevision = $this->registry->revision();
        }

        return $this->converter;
    }

    private function build(): MarkdownConverter
    {
        $lineBreaks = (bool) config('filagora.markdown.line_breaks', true);
        $nesting = config('filagora.markdown.max_nesting_level', 100);

        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => is_int($nesting) ? $nesting : 100,
            'renderer' => ['soft_break' => $lineBreaks ? "<br />\n" : "\n"],
            'external_link' => [
                'internal_hosts' => $this->internalHosts(),
                'open_in_new_window' => true,
                'html_class' => 'external-link',
                'nofollow' => 'external',
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new ExternalLinkExtension);

        foreach ($this->registry->extensions() as $extension) {
            $environment->addExtension($extension);
        }

        return new MarkdownConverter($environment);
    }

    /**
     * @return list<string>
     */
    private function internalHosts(): array
    {
        $hosts = config('filagora.markdown.internal_hosts', []);
        $hosts = is_array($hosts) ? array_values(array_filter($hosts, 'is_string')) : [];

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (is_string($appHost) && $appHost !== '') {
            $hosts[] = $appHost;
        }

        return array_values(array_unique($hosts));
    }
}
