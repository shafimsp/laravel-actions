<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Contracts;

interface Executor
{
    /**
     * Execute an action through its appropriate handler.
     *
     * @template TReturn
     *
     * @param  Action<TReturn>  $action  The action to execute
     * @return TReturn The result of handling the action
     */
    public function execute(Action $action): mixed;

    /**
     * Push a middleware onto the executor.
     *
     * @param  string|callable  $middleware  The middleware to push
     * @return $this
     */
    public function pushMiddleware(string|callable $middleware): self;
}
