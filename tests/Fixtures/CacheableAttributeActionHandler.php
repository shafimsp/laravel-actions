<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

final class CacheableAttributeActionHandler
{
    public function handle(CacheableAttributeAction $action): string
    {
        return $action->value;
    }
}
