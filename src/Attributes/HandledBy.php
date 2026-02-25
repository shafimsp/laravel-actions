<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Attributes;

use Attribute;

/**
 * Attribute to specify the handler class for an action.
 *
 * This attribute is used to explicitly define which handler class should process
 * a given action, providing a clear mapping and enabling static analysis.
 *
 * @example
 * #[HandledBy(CreateEventCommandHandler::class)]
 * final class CreateEventCommand implements ActionInterface
 * {
 *     // Action implementation
 * }
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class HandledBy
{
    /**
     * Create a new HandledBy attribute instance.
     *
     * @param  class-string  $handlerClass  The fully qualified class name of the handler
     */
    public function __construct(
        public readonly string $handlerClass
    ) {}
}
