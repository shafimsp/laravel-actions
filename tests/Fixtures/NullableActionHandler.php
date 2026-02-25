<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

final class NullableActionHandler
{
    public function handle(NullableAction $action): ?string
    {
        return $action->returnNull ? null : 'value';
    }
}
