<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Facades;

use Illuminate\Support\Facades\Facade;
use ShafiMsp\Actions\Contracts\Executor;
use ShafiMsp\Actions\Testing\FakeExecutor;

/**
 * @see \ShafiMsp\Actions\Executor
 * @see \ShafiMsp\Actions\Testing\FakeExecutor
 *
 * @method static mixed execute(\ShafiMsp\Actions\Contracts\Action $action)
 * @method static \ShafiMsp\Actions\Contracts\Executor pushMiddleware(string|callable $middleware)
 * @method static void assertExecuted(string $actionClass, \Closure|int|null $callback = null)
 * @method static void assertExecutedTimes(string $actionClass, int $times = 1)
 * @method static void assertNotExecuted(string $actionClass, ?\Closure $callback = null)
 * @method static void assertNothingExecuted()
 */
final class ActionExecutor extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @param  array<class-string<\ShafiMsp\Actions\Contracts\Action>, \Closure|mixed>  $results
     */
    public static function fake(array $results = []): FakeExecutor
    {
        $fake = new FakeExecutor($results);

        self::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return Executor::class;
    }
}
