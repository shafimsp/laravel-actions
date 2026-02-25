<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

use ShafiMsp\Actions\Attributes\HandledBy;
use ShafiMsp\Actions\Contracts\Action;

/** @implements Action<string> */
#[HandledBy(AttributeActionHandler::class)]
final class AttributeAction implements Action
{
    public function __construct(
        public readonly string $value = 'from-attribute'
    ) {}
}
