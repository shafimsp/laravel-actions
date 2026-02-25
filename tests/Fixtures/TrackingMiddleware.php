<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

use Closure;
use ShafiMsp\Actions\Contracts\Action;
use ShafiMsp\Actions\Contracts\Middleware;

final class TrackingMiddleware implements Middleware
{
    public static bool $called = false;

    public function handle(Action $action, Closure $next): mixed
    {
        self::$called = true;

        return $next($action);
    }

    public static function reset(): void
    {
        self::$called = false;
    }
}
