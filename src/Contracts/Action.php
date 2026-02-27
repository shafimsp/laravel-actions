<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Contracts;

/**
 * Interface for action objects in the CQRS pattern.
 *
 * Actions represent intentions to change application state.
 * Each action should specify its return type via the @implements docblock.
 *
 * @example
 *
 * /** @implements Action<User> *​/
 * final class FindUserQuery implements Action { ... }
 * /** @implements Action<User|null> *​/
 * final class FindOptionalUserQuery implements Action { ... }
 *
 * @template TReturn The return type of the action
 */
interface Action {}
