<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

final class VoidActionHandler
{
    public bool $handled = false;

    public function handle(VoidAction $action): void
    {
        $this->handled = true;
    }
}
