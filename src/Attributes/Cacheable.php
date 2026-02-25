<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Attributes;

use Attribute;

/**
 * Marks a query as cacheable.
 *
 * Queries marked with this attribute will have their results cached.
 * By default, the cache is kept for 60 minutes, but this can be customized.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Cacheable
{
    /**
     * Create a new cacheable attribute instance.
     *
     * @param  int  $ttl  Time to live in seconds
     * @param  ?string  $key  Custom cache key to use (optional)
     */
    public function __construct(
        public readonly int $ttl = 3600,
        public readonly ?string $key = null,
    ) {}
}
