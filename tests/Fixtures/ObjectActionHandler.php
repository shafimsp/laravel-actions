<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

final class ObjectActionHandler
{
    public function handle(ObjectAction $action): ResultObject
    {
        return new ResultObject;
    }
}
