<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests;

use ShafiMsp\Actions\ActionsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ActionsServiceProvider::class,
        ];
    }
}
