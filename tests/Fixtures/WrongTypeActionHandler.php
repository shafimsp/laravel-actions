<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

final class WrongTypeActionHandler
{
    public function handle(WrongTypeAction $action): string
    {
        return 'not-an-int';
    }
}
