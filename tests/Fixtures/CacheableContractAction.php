<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

use ShafiMsp\Actions\Contracts\Action;
use ShafiMsp\Actions\Contracts\Cacheable;

/** @implements Action<string> */
final class CacheableContractAction implements Action, Cacheable
{
    public function __construct(
        public readonly string $value = 'cached',
        private readonly string $key = 'custom-key',
        private readonly int $ttl = 120,
    ) {}

    public function cacheKey(): string
    {
        return $this->key;
    }

    public function cacheTtl(): int
    {
        return $this->ttl;
    }
}
