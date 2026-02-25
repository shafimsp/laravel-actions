<?php

declare(strict_types=1);

namespace ShafiMsp\Actions;

use Spatie\LaravelData\Data;

/**
 * Abstract base class for all actions.
 *
 * This class extends Spatie's Data class to provide structured data
 * and implements the Action contract for the CQRS pattern.
 *
 * Concrete classes must declare their return type via the @extends docblock:
 *
 * @example
 *
 * /** @extends Action<User> *​/
 * final class FindUserQuery extends Action { ... }
 *
 * @template TReturn
 *
 * @implements Contracts\Action<TReturn>
 */
abstract class Action extends Data implements Contracts\Action {}
