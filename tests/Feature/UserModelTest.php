<?php

declare(strict_types=1);

use ForumKit\Core\Exceptions\InvalidConfiguration;
use ForumKit\Core\ForumKit;
use ForumKit\Core\Support\UserModel;
use Illuminate\Foundation\Auth\User as AuthUser;

class CustomUser extends AuthUser
{
    protected $table = 'custom_users';
}

class OverrideUser extends AuthUser
{
    protected $table = 'override_users';
}

afterEach(fn () => ForumKit::flush());

it('falls back to the default auth provider model', function () {
    expect(UserModel::class())->toBe(AuthUser::class);
});

it('prefers forumkit.user_model over the auth provider', function () {
    config(['forumkit.user_model' => CustomUser::class]);

    expect(UserModel::class())->toBe(CustomUser::class)
        ->and(UserModel::table())->toBe('custom_users');
});

it('prefers ForumKit::useUserModel over config', function () {
    config(['forumkit.user_model' => CustomUser::class]);
    ForumKit::useUserModel(OverrideUser::class);

    expect(UserModel::class())->toBe(OverrideUser::class)
        ->and(UserModel::make())->toBeInstanceOf(OverrideUser::class);
});

it('rejects a class that does not exist', function () {
    config(['forumkit.user_model' => 'App\\Missing\\User']);

    UserModel::class();
})->throws(InvalidConfiguration::class, 'does not exist');

it('rejects a class that is not an Eloquent model', function () {
    config(['forumkit.user_model' => stdClass::class]);

    UserModel::class();
})->throws(InvalidConfiguration::class, 'must extend');

it('explains when nothing is configured', function () {
    config(['forumkit.user_model' => null, 'auth.providers.users.model' => null]);

    UserModel::class();
})->throws(InvalidConfiguration::class, 'No ForumKit user model');
