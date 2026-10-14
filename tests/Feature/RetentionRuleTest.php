<?php

declare(strict_types=1);

use Filagora\Core\Contracts\RetentionRule;
use Filagora\Core\Privacy\RetentionRuleRegistry;
use Filagora\Core\Privacy\TableRetentionRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('retention_scratch', function ($table) {
        $table->id();
        $table->timestamp('created_at')->nullable();
    });

    foreach ([5, 20, 40, 400] as $daysAgo) {
        DB::table('retention_scratch')->insert(['created_at' => now()->subDays($daysAgo)]);
    }
});

afterEach(fn () => Schema::dropIfExists('retention_scratch'));

it('deletes rows older than the number of days and reports how many', function () {
    $rule = new TableRetentionRule('scratch.rows', 'retention_scratch', 'created_at', fn (): int => 30);

    expect($rule->key())->toBe('scratch.rows')
        ->and($rule->prune(false))->toBe(2)
        ->and(DB::table('retention_scratch')->count())->toBe(2)
        ->and($rule->prune(false))->toBe(0);
});

it('counts without deleting on a dry run', function () {
    $rule = new TableRetentionRule('scratch.rows', 'retention_scratch', 'created_at', fn (): int => 30);

    expect($rule->prune(true))->toBe(2)
        ->and(DB::table('retention_scratch')->count())->toBe(4);
});

it('keeps everything when the days are zero or less, and reads the days each time', function () {
    $days = 0;
    $rule = new TableRetentionRule('scratch.rows', 'retention_scratch', 'created_at', function () use (&$days): int {
        return $days;
    });

    expect($rule->prune(false))->toBe(0)->and(DB::table('retention_scratch')->count())->toBe(4);

    $days = -5;
    expect($rule->prune(false))->toBe(0);

    $days = 10;
    expect($rule->prune(false))->toBe(3)->and(DB::table('retention_scratch')->count())->toBe(1);
});

it('does nothing for a table that is not there', function () {
    expect((new TableRetentionRule('gone', 'no_such_table', 'created_at', fn (): int => 30))->prune(false))->toBe(0);
});

it('holds the rules by key, a later one replacing an earlier one', function () {
    $registry = new RetentionRuleRegistry;
    $first = new TableRetentionRule('a', 'retention_scratch', 'created_at', fn (): int => 1);
    $second = new TableRetentionRule('a', 'retention_scratch', 'created_at', fn (): int => 2);
    $other = new TableRetentionRule('b', 'retention_scratch', 'created_at', fn (): int => 3);

    $registry->register($first);
    $registry->register($other);
    $registry->register($second);

    expect(array_keys($registry->all()))->toBe(['a', 'b'])
        ->and($registry->all()['a'])->toBe($second)
        ->and($registry->all()['a'])->toBeInstanceOf(RetentionRule::class)
        ->and(app(RetentionRuleRegistry::class))->toBe(app(RetentionRuleRegistry::class));
});
