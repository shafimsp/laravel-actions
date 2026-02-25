<?php

declare(strict_types=1);

namespace ShafiMsp\Actions;

use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;
use ReflectionClass;
use RuntimeException;
use ShafiMsp\Actions\Attributes\HandledBy;
use Throwable;

final class Executor implements Contracts\Executor
{
    /**
     * The middleware pipeline.
     *
     * @var array<string|callable>
     */
    private array $middleware = [];

    /**
     * Cached handler class names, keyed by action class.
     *
     * @var array<class-string, string>
     */
    private static array $handlerCache = [];

    /**
     * Cached resolved return types, keyed by action class.
     *
     * @var array<class-string, string>
     */
    private static array $returnTypeCache = [];

    /**
     * Whether the bootstrap cache has been loaded.
     */
    private static bool $cacheLoaded = false;

    /**
     * Create a new executor instance.
     */
    public function __construct(
        private readonly Container $container
    ) {}

    /**
     * Execute an action through its appropriate handler.
     *
     * @template TReturn
     *
     * @param  Contracts\Action<TReturn>  $action  The action to execute
     * @return TReturn The result of handling the action
     */
    public function execute(Contracts\Action $action): mixed
    {
        $this->loadCache();

        return $this->container->make(Pipeline::class)
            ->send($action)
            ->through($this->middleware)
            ->then(fn ($action) => $this->resolveHandler($action)($action));
    }

    /**
     * Push a middleware onto the executor.
     *
     * @param  string|callable  $middleware  The middleware to push
     */
    public function pushMiddleware(string|callable $middleware): self
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * Resolve the handler class for an action via attribute or convention.
     *
     * This is public static so the actions:cache command can use it.
     *
     * @throws Throwable
     */
    public static function resolveHandlerClass(ReflectionClass $reflection): string
    {
        $attributes = $reflection->getAttributes(HandledBy::class);

        $handlerClass = $attributes !== []
            ? $attributes[0]->newInstance()->handlerClass
            : $reflection->getName().'Handler';

        throw_unless(class_exists($handlerClass), RuntimeException::class, "Action handler [$handlerClass] does not exist.");

        throw_unless(method_exists($handlerClass, 'handle'), RuntimeException::class, "Action handler [$handlerClass] does not have a 'handle' method.");

        return $handlerClass;
    }

    /**
     * Resolve the return type from an action class's {@extends} or {@implements} docblock.
     *
     * This is public static so the actions:cache command can use it.
     */
    public static function resolveReturnTypeFromReflection(ReflectionClass $reflection): string
    {
        $docComment = $reflection->getDocComment();

        if ($docComment === false) {
            return 'void';
        }

        if (preg_match('/@(?:extends|implements)\s+[\w\\\\]*Action<([^>]+)>/i', $docComment, $matches)) {
            $type = trim($matches[1]);

            // Normalize "?Type" to "Type|null"
            if (str_starts_with($type, '?')) {
                $type = ltrim($type, '?').'|null';
            }

            $parts = array_map('trim', explode('|', $type));
            $resolved = array_map(fn (string $part) => self::resolveType($part, $reflection), $parts);

            return implode('|', $resolved);
        }

        return 'void';
    }

    /**
     * Flush the in-memory cache. Useful for testing.
     */
    public static function flushCache(): void
    {
        self::$handlerCache = [];
        self::$returnTypeCache = [];
        self::$cacheLoaded = false;
    }

    /**
     * Load the bootstrap cache file if it exists.
     */
    private function loadCache(): void
    {
        if (self::$cacheLoaded) {
            return;
        }

        self::$cacheLoaded = true;

        $cachePath = $this->container->make('config')->get('actions.cache.path') ?? $this->container->make('path.bootstrap').'/cache/actions.php';

        if (file_exists($cachePath)) {
            $manifest = require $cachePath;

            foreach ($manifest as $actionClass => $mapping) {
                self::$handlerCache[$actionClass] = $mapping['handler'];
                self::$returnTypeCache[$actionClass] = $mapping['returnType'];
            }
        }
    }

    /**
     * Get the callback for resolving the action handler.
     *
     * @template TReturn
     *
     * @param  Contracts\Action<TReturn>  $action
     *
     * @throws Throwable
     */
    private function resolveHandler(Contracts\Action $action): callable
    {
        $actionClass = $action::class;

        $handlerClass = self::$handlerCache[$actionClass] ??= self::resolveHandlerClass(new ReflectionClass($action));

        $handler = $this->container->make($handlerClass);

        $returnType = self::$returnTypeCache[$actionClass] ??= self::resolveReturnTypeFromReflection(new ReflectionClass($action));

        return function ($action) use ($handler, $returnType) {
            $result = $handler->handle($action);

            return $this->verifyReturnType($result, $returnType, $handler);
        };
    }

    /**
     * Resolve a single type to its fully qualified class name.
     *
     * @param  string  $type  A single type (e.g., 'User', 'null', 'string')
     * @param  ReflectionClass  $reflection  The reflection of the action class
     * @return string The fully qualified type
     */
    private static function resolveType(string $type, ReflectionClass $reflection): string
    {
        if (in_array($type, ['void', 'null', 'string', 'int', 'float', 'bool', 'array', 'mixed'])) {
            return $type;
        }

        // Already fully qualified
        if (str_starts_with($type, '\\')) {
            return ltrim($type, '\\');
        }

        // Try to resolve from use statements by reading the file
        $fileName = $reflection->getFileName();

        if ($fileName !== false) {
            $contents = file_get_contents($fileName);

            if ($contents !== false && preg_match('/^use\s+([\w\\\\]*\\\\'.preg_quote($type, '/').');/m', $contents, $useMatch)) {
                return $useMatch[1];
            }
        }

        // Fall back to the action's namespace
        $namespace = $reflection->getNamespaceName();

        if ($namespace !== '') {
            $fqcn = $namespace.'\\'.$type;

            if (class_exists($fqcn) || interface_exists($fqcn)) {
                return $fqcn;
            }
        }

        return $type;
    }

    /**
     * Verify that the result matches the expected return type.
     *
     * @param  mixed  $result  The result from the handler
     * @param  string  $returnType  The expected return type
     * @param  object  $handler  The handler instance
     * @return mixed The validated result
     *
     * @throws RuntimeException If the result does not match the expected type
     * @throws Throwable
     */
    private function verifyReturnType(mixed $result, string $returnType, object $handler): mixed
    {
        $types = array_map('trim', explode('|', $returnType));

        if (array_intersect($types, ['void', 'mixed']) !== []) {
            return $result;
        }

        if (is_null($result)) {
            throw_unless(in_array('null', $types), RuntimeException::class, sprintf(
                'Action handler [%s] returned null, expected [%s].',
                $handler::class,
                $returnType
            ));

            return null;
        }

        $allowedTypes = array_diff($types, ['null']);

        if (count(array_filter($allowedTypes, fn (string $type) => $this->matchesType($result, $type))) > 0) {
            return $result;
        }

        throw new RuntimeException(sprintf(
            'Action handler [%s] must return [%s], [%s] returned.',
            $handler::class,
            $returnType,
            get_debug_type($result)
        ));
    }

    /**
     * Check if a result matches a single expected type.
     */
    private function matchesType(mixed $result, string $type): bool
    {
        if (in_array($type, ['string', 'int', 'float', 'bool', 'array'])) {
            $actualType = [
                'integer' => 'int',
                'boolean' => 'bool',
                'double' => 'float',
            ][gettype($result)] ?? gettype($result);

            return $actualType === $type;
        }

        return $result instanceof $type;
    }
}
