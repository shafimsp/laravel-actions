<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

final class NonNullableActionHandler
{
    public function handle(NonNullableAction $action): mixed
    {
        return null;
    }
}
