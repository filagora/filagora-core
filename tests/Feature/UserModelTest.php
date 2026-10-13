<?php

declare(strict_types=1);

use Filagora\Core\Exceptions\InvalidConfiguration;
use Filagora\Core\Filagora;
use Filagora\Core\Support\UserModel;
use Illuminate\Foundation\Auth\User as AuthUser;

class CustomUser extends AuthUser
{
    protected $table = 'custom_users';
}

class OverrideUser extends AuthUser
{
    protected $table = 'override_users';
}

afterEach(fn () => Filagora::flush());

it('falls back to the default auth provider model', function () {
    expect(UserModel::class())->toBe(AuthUser::class);
});

it('prefers filagora.user_model over the auth provider', function () {
    config(['filagora.user_model' => CustomUser::class]);

    expect(UserModel::class())->toBe(CustomUser::class)
        ->and(UserModel::table())->toBe('custom_users');
});

it('prefers Filagora::useUserModel over config', function () {
    config(['filagora.user_model' => CustomUser::class]);
    Filagora::useUserModel(OverrideUser::class);

    expect(UserModel::class())->toBe(OverrideUser::class)
        ->and(UserModel::make())->toBeInstanceOf(OverrideUser::class);
});

it('rejects a class that does not exist', function () {
    config(['filagora.user_model' => 'App\\Missing\\User']);

    UserModel::class();
})->throws(InvalidConfiguration::class, 'does not exist');

it('rejects a class that is not an Eloquent model', function () {
    config(['filagora.user_model' => stdClass::class]);

    UserModel::class();
})->throws(InvalidConfiguration::class, 'must extend');

it('explains when nothing is configured', function () {
    config(['filagora.user_model' => null, 'auth.providers.users.model' => null]);

    UserModel::class();
})->throws(InvalidConfiguration::class, 'No Filagora user model');

it('names users by the configured title attribute, defaulting to name', function () {
    expect(UserModel::titleAttribute())->toBe('name');

    config(['filagora.user_title_attribute' => 'username']);

    expect(UserModel::titleAttribute())->toBe('username');

    config(['filagora.user_title_attribute' => '']);

    expect(UserModel::titleAttribute())->toBe('name');
});
