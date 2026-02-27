<?php

use PHPUnit\Framework\ExpectationFailedException;
use ShafiMsp\Actions\Facades\ActionExecutor;
use ShafiMsp\Actions\Testing\FakeExecutor;
use ShafiMsp\Actions\Tests\Fixtures\ObjectAction;
use ShafiMsp\Actions\Tests\Fixtures\ResultObject;
use ShafiMsp\Actions\Tests\Fixtures\StringAction;
use ShafiMsp\Actions\Tests\Fixtures\VoidAction;

afterEach(fn () => ActionExecutor::clearResolvedInstances());

// --- Facade ---

it('swaps the executor with a fake via the facade', function () {
    $fake = ActionExecutor::fake();

    expect($fake)->toBeInstanceOf(FakeExecutor::class);
});

// --- Return Values ---

it('returns null by default', function () {
    ActionExecutor::fake();

    expect(ActionExecutor::execute(new StringAction))->toBeNull();
});

it('returns a static fake value', function () {
    ActionExecutor::fake([
        StringAction::class => 'faked',
    ]);

    expect(ActionExecutor::execute(new StringAction))->toBe('faked');
});

it('returns a value from a closure', function () {
    ActionExecutor::fake([
        StringAction::class => fn (StringAction $action) => "faked-{$action->value}",
    ]);

    expect(ActionExecutor::execute(new StringAction('test')))->toBe('faked-test');
});

it('returns different values for different actions', function () {
    ActionExecutor::fake([
        StringAction::class => 'string-result',
        ObjectAction::class => new ResultObject('fake-object'),
    ]);

    expect(ActionExecutor::execute(new StringAction))->toBe('string-result');

    $result = ActionExecutor::execute(new ObjectAction);
    expect($result)->toBeInstanceOf(ResultObject::class);
    expect($result->value)->toBe('fake-object');
});

// --- assertExecuted ---

it('asserts an action was executed', function () {
    ActionExecutor::fake();
    ActionExecutor::execute(new StringAction);

    ActionExecutor::assertExecuted(StringAction::class);
});

it('fails when asserting an unexecuted action was executed', function () {
    ActionExecutor::fake();

    ActionExecutor::assertExecuted(StringAction::class);
})->throws(ExpectationFailedException::class);

it('asserts an action was executed with a truth test', function () {
    ActionExecutor::fake();
    ActionExecutor::execute(new StringAction('match-me'));

    ActionExecutor::assertExecuted(StringAction::class, fn (StringAction $action) => $action->value === 'match-me');
});

it('fails when truth test does not match', function () {
    ActionExecutor::fake();
    ActionExecutor::execute(new StringAction('other'));

    ActionExecutor::assertExecuted(StringAction::class, fn (StringAction $action) => $action->value === 'no-match');
})->throws(ExpectationFailedException::class);

// --- assertExecutedTimes ---

it('asserts an action was executed a specific number of times', function () {
    ActionExecutor::fake();
    ActionExecutor::execute(new StringAction);
    ActionExecutor::execute(new StringAction);

    ActionExecutor::assertExecuted(StringAction::class, 2);
});

it('fails when count does not match', function () {
    ActionExecutor::fake();
    ActionExecutor::execute(new StringAction);

    ActionExecutor::assertExecutedTimes(StringAction::class, 3);
})->throws(ExpectationFailedException::class);

// --- assertExecutedWith ---

it('asserts an action was executed with matching properties', function () {
    ActionExecutor::fake();
    ActionExecutor::execute(new StringAction('match-me'));

    ActionExecutor::assertExecutedWith(StringAction::class, ['value' => 'match-me']);
});

it('fails when properties do not match', function () {
    ActionExecutor::fake();
    ActionExecutor::execute(new StringAction('other'));

    ActionExecutor::assertExecutedWith(StringAction::class, ['value' => 'no-match']);
})->throws(ExpectationFailedException::class);

// --- assertNotExecuted ---

it('asserts an action was not executed', function () {
    ActionExecutor::fake();

    ActionExecutor::assertNotExecuted(StringAction::class);
});

it('fails when asserting a executed action was not executed', function () {
    ActionExecutor::fake();
    ActionExecutor::execute(new StringAction);

    ActionExecutor::assertNotExecuted(StringAction::class);
})->throws(ExpectationFailedException::class);

it('asserts an action was not executed with a truth test', function () {
    ActionExecutor::fake();
    ActionExecutor::execute(new StringAction('other'));

    ActionExecutor::assertNotExecuted(StringAction::class, fn (StringAction $action) => $action->value === 'no-match');
});

// --- assertNothingExecuted ---

it('asserts nothing was executed', function () {
    ActionExecutor::fake();

    ActionExecutor::assertNothingExecuted();
});

it('fails when actions were executed', function () {
    ActionExecutor::fake();
    ActionExecutor::execute(new VoidAction);

    ActionExecutor::assertNothingExecuted();
})->throws(ExpectationFailedException::class);
