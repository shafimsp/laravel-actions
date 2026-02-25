<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

final class StringActionHandler
{
    public function handle(StringAction $action): string
    {
        return $action->value;
    }
}
