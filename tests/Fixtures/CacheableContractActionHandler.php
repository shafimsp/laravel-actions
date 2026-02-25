<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

final class CacheableContractActionHandler
{
    public function handle(CacheableContractAction $action): string
    {
        return $action->value;
    }
}
