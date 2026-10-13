<?php

declare(strict_types=1);

namespace Filagora\Core\Exceptions;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class InvalidConfiguration extends InvalidArgumentException
{
    public static function userModelNotFound(string $class): self
    {
        return new self("Filagora user model [{$class}] does not exist.");
    }

    public static function userModelNotEloquent(string $class): self
    {
        return new self("Filagora user model [{$class}] must extend ".Model::class.'.');
    }

    public static function userModelMissing(): self
    {
        return new self('No Filagora user model is configured and auth.providers.users.model is not set. Set filagora.user_model or call Filagora::useUserModel().');
    }

    public static function moduleNotRegistered(string $id): self
    {
        return new self("Filagora module [{$id}] is not registered.");
    }
}
