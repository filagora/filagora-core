<?php

declare(strict_types=1);

use Filagora\Core\Contracts\AccessChecker;
use Filagora\Core\Filament\Concerns\GatedByAbility;
use Filagora\Core\Support\DenyAllAccessChecker;
use Filagora\Core\Support\GateAbilities;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

function keyedChecker(array $allowed): AccessChecker
{
    return new class($allowed) implements AccessChecker
    {
        /** @param array<int|string, list<string>> $allowed user id => keys */
        public function __construct(private array $allowed) {}

        public function allows(?Authenticatable $user, string $key): bool
        {
            return $user !== null && in_array($key, $this->allowed[$user->getAuthIdentifier()] ?? [], true);
        }

        public function allowsAny(?Authenticatable $user, array $keys): bool
        {
            return array_filter($keys, fn (string $key): bool => $this->allows($user, $key)) !== [];
        }

        public function allowsAll(?Authenticatable $user, array $keys): bool
        {
            return array_filter($keys, fn (string $key): bool => ! $this->allows($user, $key)) === [];
        }
    };
}

function someUser(int $id = 1): GenericUser
{
    return new GenericUser(['id' => $id]);
}

it('denies everything until something is bound', function () {
    $checker = app(AccessChecker::class);

    expect($checker)->toBeInstanceOf(DenyAllAccessChecker::class)
        ->and($checker->allows(someUser(), 'any.key'))->toBeFalse()
        ->and($checker->allows(null, 'any.key'))->toBeFalse()
        ->and($checker->allowsAny(someUser(), ['a', 'b']))->toBeFalse()
        ->and($checker->allowsAll(someUser(), []))->toBeFalse();
});

it('uses the checker a package or the host binds instead', function () {
    app()->bind(AccessChecker::class, fn () => keyedChecker([1 => ['a']]));

    $checker = app(AccessChecker::class);

    expect($checker->allows(someUser(1), 'a'))->toBeTrue()
        ->and($checker->allows(someUser(2), 'a'))->toBeFalse();
});

it('defines gate abilities from a config map that ask the bound checker', function () {
    config(['pkg.gates' => ['pkg.view' => 'keys.view', 'pkg.edit' => 'keys.edit', 'bad' => '', 7 => 'numeric']]);
    app()->bind(AccessChecker::class, fn () => keyedChecker([1 => ['keys.view']]));

    GateAbilities::define('pkg.gates');

    expect(Gate::forUser(someUser(1))->allows('pkg.view'))->toBeTrue()
        ->and(Gate::forUser(someUser(1))->allows('pkg.edit'))->toBeFalse()
        ->and(Gate::forUser(someUser(2))->allows('pkg.view'))->toBeFalse()
        ->and(Gate::has('bad'))->toBeFalse()
        ->and(GateAbilities::keys('pkg.gates'))->toBe(['keys.view', 'keys.edit'])
        ->and(GateAbilities::keys('pkg.missing'))->toBe([]);
});

it('lets a guest through no gate', function () {
    config(['pkg.gates' => ['pkg.view' => 'keys.view']]);
    app()->bind(AccessChecker::class, fn () => keyedChecker([1 => ['keys.view']]));
    GateAbilities::define('pkg.gates');

    expect(Gate::forUser(null)->allows('pkg.view'))->toBeFalse();
});

it('gates a resource action behind its ability, and denies one with no ability', function () {
    $resource = new class
    {
        use GatedByAbility;

        protected static function abilityFor(string|UnitEnum $action, ?Model $record): ?string
        {
            return $action === 'delete' ? null : 'pkg.view';
        }

        public static function response(string $action): bool
        {
            return self::getAuthorizationResponse($action)->allowed();
        }
    };

    Gate::define('pkg.view', fn (?Authenticatable $user) => $user !== null);

    expect($resource::response('viewAny'))->toBeFalse();

    test()->actingAs(someUser());
    expect($resource::response('viewAny'))->toBeTrue()
        ->and($resource::response('delete'))->toBeFalse();
});
