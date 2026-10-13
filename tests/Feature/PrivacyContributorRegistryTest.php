<?php

declare(strict_types=1);

use Filagora\Core\Contracts\PrivacyContributor;
use Filagora\Core\Privacy\PrivacyContributorRegistry;
use Illuminate\Database\Eloquent\Model;

function contributor(string $key, string $marker): PrivacyContributor
{
    return new class($key, $marker) implements PrivacyContributor
    {
        public function __construct(private string $key, private string $marker) {}

        public function key(): string
        {
            return $this->key;
        }

        public function export(Model $user): array
        {
            return ['marker' => $this->marker];
        }

        public function erase(Model $user): void {}
    };
}

it('collects contributors by key and replaces a duplicate key', function () {
    $registry = app(PrivacyContributorRegistry::class);
    $registry->register(contributor('forums', 'first'));
    $registry->register(contributor('logs', 'logs'));
    $registry->register(contributor('forums', 'second'));

    $all = $registry->all();

    expect(array_keys($all))->toBe(['forums', 'logs'])
        ->and($all['forums']->export(new class extends Model {}))->toBe(['marker' => 'second']);
});
