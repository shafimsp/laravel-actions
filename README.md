# Laravel Actions

A lightweight action executor with middleware pipeline for Laravel applications. Implements the CQRS pattern with convention-based handler resolution, return type validation, caching, and first-class testing support.

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x

## Installation

```bash
composer require shafimsp/laravel-actions
```

The package auto-registers its service provider. Optionally publish the config:

```bash
php artisan vendor:publish --tag=actions-config
```

## Quick Start

### 1. Define an Action

Actions are simple classes that implement the `Action` interface and declare their return type via a docblock:

```php
use ShafiMsp\Actions\Contracts\Action;

/** @implements Action<User> */
final class FindUserById implements Action
{
    public function __construct(
        public readonly int $id,
    ) {}
}
```

### 2. Create a Handler

By convention, the handler class is the action class name suffixed with `Handler` in the same namespace:

```php
final class FindUserByIdHandler
{
    public function handle(FindUserById $action): User
    {
        return User::findOrFail($action->id);
    }
}
```

### 3. Execute the Action

```php
// Using the global helper
$user = execute(new FindUserById(1));

// Using the facade
use ShafiMsp\Actions\Facades\ActionExecutor;

$user = ActionExecutor::execute(new FindUserById(1));
```

## Defining Actions

Declare the return type using the `@implements` docblock:

```php
/** @implements Action<string> */
final class GetGreeting implements Action {}

/** @implements Action<User|null> */
final class FindOptionalUser implements Action {}

/** @implements Action<void> */
final class SendEmail implements Action {}

/** @implements Action<ResultA|ResultB|null> */
final class GetFlexibleResult implements Action {}
```

Supported return types: scalars (`string`, `int`, `float`, `bool`, `array`), objects, nullable types, union types, `void`, and `mixed`.

The executor validates the handler's return value against the declared type at runtime and throws a `RuntimeException` on mismatch.

## Handler Resolution

### Convention-Based (Default)

Append `Handler` to the action class name in the same namespace:

| Action | Handler |
|---|---|
| `App\Actions\CreateUser` | `App\Actions\CreateUserHandler` |
| `App\Queries\FindUser` | `App\Queries\FindUserHandler` |

### Attribute-Based

Use the `#[HandledBy]` attribute to specify a custom handler:

```php
use ShafiMsp\Actions\Attributes\HandledBy;

#[HandledBy(CustomHandler::class)]
final class CreateUser implements Action {}
```

## Middleware

Middleware wraps the action execution pipeline, enabling cross-cutting concerns like logging, authorization, or caching.

### Class-Based Middleware

```php
use ShafiMsp\Actions\Contracts\Action;
use ShafiMsp\Actions\Contracts\Middleware;

final class LoggingMiddleware implements Middleware
{
    public function handle(Action $action, Closure $next): mixed
    {
        Log::info('Executing: ' . $action::class);
        $result = $next($action);
        Log::info('Completed: ' . $action::class);

        return $result;
    }
}
```

### Closure-Based Middleware

```php
use ShafiMsp\Actions\Facades\ActionExecutor;

ActionExecutor::pushMiddleware(function (Action $action, Closure $next) {
    // before
    $result = $next($action);
    // after
    return $result;
});
```

### Global Middleware

Register middleware for every action in `config/actions.php`:

```php
return [
    'middleware' => [
        \ShafiMsp\Actions\Middleware\CacheMiddleware::class,
        \App\Actions\Middleware\LoggingMiddleware::class,
    ],
];
```

Middleware can short-circuit the pipeline by returning early without calling `$next()`, or modify the action before passing it along.

## Caching

Cache action results automatically using the `#[Cacheable]` attribute:

```php
use ShafiMsp\Actions\Attributes\Cacheable;

#[Cacheable(ttl: 3600)]
final class GetDashboardStats implements Action {}

#[Cacheable(ttl: 600, key: 'all_users')]
final class ListAllUsers implements Action {}
```

**Parameters:**
- `ttl` — Time to live in seconds (default: `3600`)
- `key` — Custom cache key (optional, auto-generated from action class and properties if omitted)

The `CacheMiddleware` is included in the default middleware stack. It uses cache tags when available and a distributed lock to prevent cache stampede.

## Bootstrap Cache

For production, generate a bootstrap cache to skip reflection on every request:

```bash
php artisan actions:cache
```

This discovers all actions, resolves their handlers and return types, and writes a cache file to `bootstrap/cache/actions.php`.

To clear:

```bash
php artisan actions:clear
```

## Configuration

```php
// config/actions.php

return [
    // Global middleware applied to every action execution
    'middleware' => [
        \ShafiMsp\Actions\Middleware\CacheMiddleware::class,
    ],

    // Bootstrap cache settings
    'cache' => [
        'enabled' => true,
        'directories' => [app_path()],
        'path' => null, // defaults to bootstrap/cache/actions.php
    ],
];
```

## Testing

The package provides a `FakeExecutor` with a fluent assertion API.

### Faking the Executor

```php
use ShafiMsp\Actions\Facades\ActionExecutor;

// Fake all actions (returns null by default)
ActionExecutor::fake();

// Fake with specific return values
ActionExecutor::fake([
    FindUserById::class => User::factory()->create(),
    GetGreeting::class => 'Hello!',
]);

// Fake with closures for dynamic values
ActionExecutor::fake([
    FindUserById::class => fn (FindUserById $action) => User::find($action->id),
]);
```

### Assertions

```php
// Assert an action was executed
ActionExecutor::assertExecuted(FindUserById::class);

// Assert with a truth test
ActionExecutor::assertExecuted(
    FindUserById::class,
    fn (FindUserById $action) => $action->id === 1
);

// Assert executed exactly N times
ActionExecutor::assertExecutedTimes(FindUserById::class, 2);

// Assert an action was NOT executed
ActionExecutor::assertNotExecuted(DeleteUser::class);

// Assert nothing was executed at all
ActionExecutor::assertNothingExecuted();
```

### Full Test Example

```php
public function test_show_returns_user(): void
{
    $user = User::factory()->create();

    ActionExecutor::fake([
        FindUserById::class => $user,
    ]);

    $response = $this->get("/users/{$user->id}");

    $response->assertOk();

    ActionExecutor::assertExecuted(
        FindUserById::class,
        fn (FindUserById $action) => $action->id === $user->id
    );
}
```

## License

MIT
