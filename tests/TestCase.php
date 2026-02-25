<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use ShafiMsp\Actions\ActionsServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ActionsServiceProvider::class,
        ];
    }
}
