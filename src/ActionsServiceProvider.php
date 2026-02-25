<?php

declare(strict_types=1);

namespace ShafiMsp\Actions;

use ShafiMsp\Actions\Commands\ActionsCacheCommand;
use ShafiMsp\Actions\Commands\ActionsClearCommand;
use ShafiMsp\Actions\Contracts\Executor as ExecutorContract;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ActionsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('actions')
            ->hasConfigFile()
            ->hasCommands(
                ActionsCacheCommand::class,
                ActionsClearCommand::class,
            );
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ExecutorContract::class, fn ($app): ExecutorContract => new Executor($app));

        $this->optimizes(
            optimize: 'actions:cache',
            clear: 'actions:clear',
        );
    }

    public function packageBooted(): void
    {
        $this->app->resolving(ExecutorContract::class, function (ExecutorContract $executor): ExecutorContract {
            foreach (config('actions.middleware', []) as $middleware) {
                $executor->pushMiddleware($middleware);
            }

            return $executor;
        });
    }
}
