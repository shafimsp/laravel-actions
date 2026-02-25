<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

final class AttributeActionHandler
{
    public function handle(AttributeAction $action): string
    {
        return $action->value;
    }
}
