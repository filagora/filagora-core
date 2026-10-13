<?php

declare(strict_types=1);

use Filagora\Core\Support\Tables;
use Filagora\Core\Support\UserKey;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

it('creates an unsigned bigint user column', function () {
    $table = Tables::name('user_key_probe');

    Schema::create($table, function (Blueprint $blueprint) {
        $blueprint->id();
        UserKey::column($blueprint);
        UserKey::column($blueprint, 'moderator_id')->nullable();
    });

    $columns = collect(Schema::getColumns($table))->keyBy('name');

    expect($columns['user_id']['type'])->toContain('bigint')
        ->and($columns['user_id']['type'])->toContain('unsigned')
        ->and($columns['user_id']['nullable'])->toBeFalse()
        ->and($columns['moderator_id']['nullable'])->toBeTrue();

    Schema::drop($table);
});
