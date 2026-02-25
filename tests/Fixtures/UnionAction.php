<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

use ShafiMsp\Actions\Contracts\Action;

/** @implements Action<ResultObject|SecondResultObject|null> */
final class UnionAction implements Action
{
    public function __construct(
        public readonly string $returnType = 'first'
    ) {}
}
