<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

final class UnionActionHandler
{
    public function handle(UnionAction $action): ResultObject|SecondResultObject|null
    {
        return match ($action->returnType) {
            'first' => new ResultObject,
            'second' => new SecondResultObject,
            'null' => null,
        };
    }
}
