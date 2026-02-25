<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Contracts;

use Closure;

/**
 * Interface for action middleware.
 */
interface Middleware
{
    /**
     * Handle the action.
     *
     * @template TReturn
     *
     * @param  Action<TReturn>  $action  The action to handle
     * @param  Closure  $next  The next middleware in the chain
     * @return TReturn The result of handling the action
     */
    public function handle(Action $action, Closure $next): mixed;
}
