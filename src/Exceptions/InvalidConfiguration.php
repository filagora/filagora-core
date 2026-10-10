<?php

declare(strict_types=1);

namespace ForumKit\Core\Exceptions;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class InvalidConfiguration extends InvalidArgumentException
{
    public static function userModelNotFound(string $class): self
    {
        return new self("ForumKit user model [{$class}] does not exist.");
    }

    public static function userModelNotEloquent(string $class): self
    {
        return new self("ForumKit user model [{$class}] must extend ".Model::class.'.');
    }

    public static function userModelMissing(): self
    {
        return new self('No ForumKit user model is configured and auth.providers.users.model is not set. Set forumkit.user_model or call ForumKit::useUserModel().');
    }
}
