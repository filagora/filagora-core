<?php

declare(strict_types=1);

use Filagora\Core\Content\ExtensionRegistry;
use Filagora\Core\Content\MarkdownRenderer;
use Filagora\Core\Contracts\HtmlTransformer;

function markdown(): MarkdownRenderer
{
    return app(MarkdownRenderer::class);
}

it('renders commonmark and github flavoured syntax', function () {
    $html = markdown()->render("**bold** ~~gone~~\n\n| a | b |\n|---|---|\n| 1 | 2 |");

    expect($html)->toContain('<strong>bold</strong>')
        ->and($html)->toContain('<del>gone</del>')
        ->and($html)->toContain('<table>');
});

it('returns an empty string for empty input', function () {
    expect(markdown()->render(null))->toBe('')->and(markdown()->render(''))->toBe('');
});

it('escapes raw html', function () {
    $html = markdown()->render('<script>alert(1)</script> <b onclick="x()">hi</b>');

    expect($html)->not->toContain('<script>')
        ->and($html)->not->toContain('<b onclick')
        ->and($html)->toContain('&lt;script&gt;');
});

it('drops unsafe link schemes', function () {
    $html = markdown()->render('[click](javascript:alert(1)) [data](data:text/html;base64,AAAA)');

    expect($html)->not->toContain('javascript:')->and($html)->not->toContain('data:text');
});

it('marks external links and leaves internal ones alone', function () {
    config(['app.url' => 'https://forum.example.test', 'filagora.markdown.internal_hosts' => ['docs.example.test']]);

    $html = (new MarkdownRenderer(new ExtensionRegistry))->render(
        '[ext](https://other.example.org/x) [self](https://forum.example.test/a) [docs](https://docs.example.test/b)'
    );

    expect($html)->toMatch('/<a [^>]*href="https:\/\/other\.example\.org\/x"[^>]*>/')
        ->and($html)->toContain('rel="nofollow noopener noreferrer"')
        ->and($html)->toContain('target="_blank"')
        ->and($html)->toContain('class="external-link"')
        ->and($html)->toContain('<a href="https://forum.example.test/a">')
        ->and($html)->toContain('<a href="https://docs.example.test/b">');
});

it('turns a single newline into a visible break unless disabled', function () {
    expect(markdown()->render("one\ntwo"))->toContain("one<br />\ntwo");

    config(['filagora.markdown.line_breaks' => false]);

    expect((new MarkdownRenderer(new ExtensionRegistry))->render("one\ntwo"))->not->toContain('<br />');
});

it('runs registered transformers in priority order', function () {
    $registry = new ExtensionRegistry;
    $append = fn (string $suffix) => new class($suffix) implements HtmlTransformer
    {
        public function __construct(private string $suffix) {}

        public function transform(string $html): string
        {
            return $html.$this->suffix;
        }
    };
    $registry->transformer($append('B'), priority: 200);
    $registry->transformer($append('A'), priority: 100);

    expect((new MarkdownRenderer($registry))->render('x'))->toBe("<p>x</p>\nAB");
});

it('rebuilds when an extension is registered after first use', function () {
    $registry = new ExtensionRegistry;
    $renderer = new MarkdownRenderer($registry);

    expect($renderer->render('~~x~~'))->toContain('<del>');

    $registry->transformer(new class implements HtmlTransformer
    {
        public function transform(string $html): string
        {
            return strtoupper($html);
        }
    });

    expect($renderer->render('hi'))->toBe("<P>HI</P>\n");
});
