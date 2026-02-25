<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Contracts;

/**
 * Contract for actions that provide runtime cache configuration.
 *
 * Implement this interface to compute cache key and TTL dynamically
 * at runtime instead of using the static #[Cacheable] attribute.
 */
interface Cacheable
{
    public function cacheKey(): string;

    public function cacheTtl(): int;
}
