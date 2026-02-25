<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

final class CacheableAttributeWithKeyActionHandler
{
    public function handle(CacheableAttributeWithKeyAction $action): string
    {
        return $action->value;
    }
}
