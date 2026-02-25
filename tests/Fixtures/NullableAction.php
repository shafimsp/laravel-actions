<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

use ShafiMsp\Actions\Contracts\Action;

/** @implements Action<string|null> */
final class NullableAction implements Action
{
    public function __construct(
        public readonly bool $returnNull = false
    ) {}
}
