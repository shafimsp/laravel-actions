<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

final class SecondResultObject
{
    public function __construct(
        public readonly string $value = 'second'
    ) {}
}
