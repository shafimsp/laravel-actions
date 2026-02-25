<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Commands;

use ShafiMsp\Actions\Contracts\Action;
use ShafiMsp\Actions\Executor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

class ActionsCacheCommand extends Command
{
    protected $signature = 'actions:cache';

    protected $description = 'Cache the action handler and return type mappings';

    public function handle(): void
    {
        $this->callSilent('actions:clear');

        $actions = $this->discoverActions();

        if ($actions === []) {
            $this->components->info('No action classes found.');

            return;
        }

        $manifest = [];

        foreach ($actions as $actionClass) {
            $reflection = new ReflectionClass($actionClass);

            $manifest[$actionClass] = [
                'handler' => Executor::resolveHandlerClass($reflection),
                'returnType' => Executor::resolveReturnTypeFromReflection($reflection),
            ];
        }

        File::put(
            config('actions.cache.path') ?? $this->laravel->bootstrapPath('cache/actions.php'),
            '<?php return '.var_export($manifest, true).';'.PHP_EOL
        );

        $this->components->info(sprintf('Action mappings cached successfully. [%d actions]', count($manifest)));
    }

    /**
     * Discover all classes implementing the Action contract.
     *
     * @return array<class-string>
     */
    private function discoverActions(): array
    {
        $directories = config('actions.cache.directories', [app_path()]);
        $classes = [];

        $finder = (new Finder)
            ->files()
            ->name('*.php')
            ->in($directories);

        foreach ($finder as $file) {
            $class = $this->classFromFile($file->getRealPath());

            if ($class === null) {
                continue;
            }

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract() || $reflection->isInterface()) {
                continue;
            }

            if ($reflection->implementsInterface(Action::class)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    /**
     * Extract the fully qualified class name from a file path.
     */
    private function classFromFile(string $path): ?string
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $namespace = null;
        $class = null;

        if (preg_match('/^namespace\s+(.+?);/m', $contents, $matches)) {
            $namespace = $matches[1];
        }

        if (preg_match('/^(?:final\s+|abstract\s+|readonly\s+)*class\s+(\w+)/m', $contents, $matches)) {
            $class = $matches[1];
        }

        if ($namespace === null || $class === null) {
            return null;
        }

        return $namespace.'\\'.$class;
    }
}
