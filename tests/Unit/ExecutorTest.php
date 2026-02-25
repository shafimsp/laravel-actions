<?php

use ShafiMsp\Actions\Contracts\Action;
use ShafiMsp\Actions\Executor;
use ShafiMsp\Actions\Tests\Fixtures\AttributeAction;
use ShafiMsp\Actions\Tests\Fixtures\MissingHandlerAction;
use ShafiMsp\Actions\Tests\Fixtures\NoDocblockAction;
use ShafiMsp\Actions\Tests\Fixtures\NoHandleMethodAction;
use ShafiMsp\Actions\Tests\Fixtures\NonNullableAction;
use ShafiMsp\Actions\Tests\Fixtures\NullableAction;
use ShafiMsp\Actions\Tests\Fixtures\ObjectAction;
use ShafiMsp\Actions\Tests\Fixtures\ResultObject;
use ShafiMsp\Actions\Tests\Fixtures\SecondResultObject;
use ShafiMsp\Actions\Tests\Fixtures\StringAction;
use ShafiMsp\Actions\Tests\Fixtures\TrackingMiddleware;
use ShafiMsp\Actions\Tests\Fixtures\UnionAction;
use ShafiMsp\Actions\Tests\Fixtures\VoidAction;
use ShafiMsp\Actions\Tests\Fixtures\WrongTypeAction;

beforeEach(function () {
    Executor::flushCache();
    $this->executor = new Executor(app());
});

// --- Handler Resolution ---

it('executes an action through its convention-based handler', function () {
    $result = $this->executor->execute(new StringAction('hello'));

    expect($result)->toBe('hello');
});

it('executes an action through its HandledBy attribute handler', function () {
    $result = $this->executor->execute(new AttributeAction('from-attribute'));

    expect($result)->toBe('from-attribute');
});

it('throws when no handler class exists', function () {
    $this->executor->execute(new MissingHandlerAction);
})->throws(RuntimeException::class, 'does not exist');

it('throws when handler has no handle method', function () {
    $this->executor->execute(new NoHandleMethodAction);
})->throws(RuntimeException::class, "does not have a 'handle' method");

// --- Return Type: Scalar ---

it('returns a scalar value matching the declared type', function () {
    $result = $this->executor->execute(new StringAction('world'));

    expect($result)->toBe('world');
});

it('throws when handler returns wrong scalar type', function () {
    $this->executor->execute(new WrongTypeAction);
})->throws(RuntimeException::class, 'must return [int]');

// --- Return Type: Object ---

it('returns an object matching the declared type', function () {
    $result = $this->executor->execute(new ObjectAction);

    expect($result)->toBeInstanceOf(ResultObject::class);
});

// --- Return Type: Void ---

it('returns null for void return type', function () {
    $result = $this->executor->execute(new VoidAction);

    expect($result)->toBeNull();
});

it('defaults to void when action has no docblock', function () {
    $result = $this->executor->execute(new NoDocblockAction);

    expect($result)->toBeNull();
});

// --- Return Type: Nullable ---

it('allows null when return type includes null', function () {
    $result = $this->executor->execute(new NullableAction(returnNull: true));

    expect($result)->toBeNull();
});

it('returns a value when nullable type has a value', function () {
    $result = $this->executor->execute(new NullableAction(returnNull: false));

    expect($result)->toBe('value');
});

it('throws when handler returns null for non-nullable type', function () {
    $this->executor->execute(new NonNullableAction);
})->throws(RuntimeException::class, 'returned null');

// --- Return Type: Union ---

it('accepts the first type in a union', function () {
    $result = $this->executor->execute(new UnionAction(returnType: 'first'));

    expect($result)->toBeInstanceOf(ResultObject::class);
});

it('accepts the second type in a union', function () {
    $result = $this->executor->execute(new UnionAction(returnType: 'second'));

    expect($result)->toBeInstanceOf(SecondResultObject::class);
});

it('accepts null in a nullable union', function () {
    $result = $this->executor->execute(new UnionAction(returnType: 'null'));

    expect($result)->toBeNull();
});

// --- Middleware ---

it('executes middleware in order', function () {
    $log = [];

    $this->executor->pushMiddleware(function (Action $action, Closure $next) use (&$log) {
        $log[] = 'before-1';
        $result = $next($action);
        $log[] = 'after-1';

        return $result;
    });

    $this->executor->pushMiddleware(function (Action $action, Closure $next) use (&$log) {
        $log[] = 'before-2';
        $result = $next($action);
        $log[] = 'after-2';

        return $result;
    });

    $this->executor->execute(new StringAction('test'));

    expect($log)->toBe(['before-1', 'before-2', 'after-2', 'after-1']);
});

it('executes class-based middleware', function () {
    TrackingMiddleware::reset();

    $this->executor->pushMiddleware(TrackingMiddleware::class);
    $this->executor->execute(new StringAction);

    expect(TrackingMiddleware::$called)->toBeTrue();
});

it('allows middleware to short-circuit the pipeline', function () {
    $this->executor->pushMiddleware(function (Action $action, Closure $next) {
        return 'short-circuited';
    });

    $result = $this->executor->execute(new StringAction);

    expect($result)->toBe('short-circuited');
});

it('allows middleware to modify the action before handling', function () {
    $this->executor->pushMiddleware(function (Action $action, Closure $next) {
        return $next(new StringAction('modified'));
    });

    $result = $this->executor->execute(new StringAction('original'));

    expect($result)->toBe('modified');
});
