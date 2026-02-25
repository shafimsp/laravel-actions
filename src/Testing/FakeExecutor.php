<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Testing;

use Closure;
use ShafiMsp\Actions\Contracts\Action;
use ShafiMsp\Actions\Contracts\Executor;
use PHPUnit\Framework\Assert as PHPUnit;

final class FakeExecutor implements Executor
{
    /**
     * The actions that have been executed.
     *
     * @var array<int, Action>
     */
    private array $executed = [];

    /**
     * The fake return values keyed by action class.
     *
     * @var array<class-string<Action>, Closure|mixed>
     */
    private array $results = [];

    /**
     * @param  array<class-string<Action>, Closure|mixed>  $results
     */
    public function __construct(array $results = [])
    {
        $this->results = $results;
    }

    public function execute(Action $action): mixed
    {
        $this->executed[] = $action;

        $result = $this->results[$action::class] ?? null;

        return $result instanceof Closure ? $result($action) : $result;
    }

    public function pushMiddleware(string|callable $middleware): self
    {
        return $this;
    }

    /**
     * Assert that an action was executed.
     *
     * @param  class-string<Action>  $actionClass
     * @param  (Closure(Action): bool)|int|null  $callback
     */
    public function assertExecuted(string $actionClass, Closure|int|null $callback = null): void
    {
        if (is_int($callback)) {
            $this->assertExecutedTimes($actionClass, $callback);

            return;
        }

        $count = $this->executed($actionClass, $callback);

        PHPUnit::assertTrue(
            $count > 0,
            "The expected [{$actionClass}] action was not executed."
        );
    }

    /**
     * Assert that an action was executed a specific number of times.
     *
     * @param  class-string<Action>  $actionClass
     */
    public function assertExecutedTimes(string $actionClass, int $times = 1): void
    {
        $count = $this->executed($actionClass);

        PHPUnit::assertSame(
            $times,
            $count,
            "The expected [{$actionClass}] action was executed {$count} times instead of {$times} times."
        );
    }

    /**
     * Assert that an action was not executed.
     *
     * @param  class-string<Action>  $actionClass
     * @param  (Closure(Action): bool)|null  $callback
     */
    public function assertNotExecuted(string $actionClass, ?Closure $callback = null): void
    {
        $count = $this->executed($actionClass, $callback);

        PHPUnit::assertSame(
            0,
            $count,
            "The unexpected [{$actionClass}] action was executed {$count} times."
        );
    }

    /**
     * Assert that no actions were executed.
     */
    public function assertNothingExecuted(): void
    {
        $count = count($this->executed);

        PHPUnit::assertSame(
            0,
            $count,
            "{$count} actions were executed unexpectedly."
        );
    }

    /**
     * Get the count of executed actions matching the given class and optional callback.
     *
     * @param  class-string<Action>  $actionClass
     * @param  (Closure(Action): bool)|null  $callback
     */
    private function executed(string $actionClass, ?Closure $callback = null): int
    {
        $actions = array_filter(
            $this->executed,
            fn (Action $action) => $action instanceof $actionClass
                && ($callback === null || $callback($action))
        );

        return count($actions);
    }
}
