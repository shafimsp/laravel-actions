<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Contracts;

/**
 * Interface for action objects in the CQRS pattern.
 *
 * Actions represent intentions to change application state.
 * Each action should specify its return type via the @extends docblock.
 *
 * @example
 *
 * /** @extends Action<User> *​/
 * final class FindUserQuery extends Action { ... }
 * /** @extends Action<User|null> *​/
 * final class FindOptionalUserQuery extends Action { ... }
 *
 * @template TReturn The return type of the action
 */
interface Action {}
