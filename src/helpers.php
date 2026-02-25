<?php

declare(strict_types=1);

use ShafiMsp\Actions\Contracts\Action;
use ShafiMsp\Actions\Contracts\Executor;

if (! function_exists('execute')) {
    /**
     * Execute an action through its appropriate handler.
     *
     * @template TReturn
     *
     * @param  Action<TReturn>  $action
     * @return TReturn
     */
    function execute(Action $action): mixed
    {
        return app(Executor::class)->execute($action);
    }
}
