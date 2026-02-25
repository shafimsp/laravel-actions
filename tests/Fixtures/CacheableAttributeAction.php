<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

use ShafiMsp\Actions\Attributes\Cacheable;
use ShafiMsp\Actions\Contracts\Action;

/** @implements Action<string> */
#[Cacheable(ttl: 300)]
final class CacheableAttributeAction implements Action
{
    public function __construct(
        public readonly string $value = 'cached',
    ) {}
}
