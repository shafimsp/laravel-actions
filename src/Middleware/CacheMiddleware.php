<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Middleware;

use Closure;
use ShafiMsp\Actions\Attributes\Cacheable;
use ShafiMsp\Actions\Contracts\Action;
use ShafiMsp\Actions\Contracts\Cacheable as CacheableContract;
use ShafiMsp\Actions\Contracts\Middleware;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;

/**
 * Middleware to cache query results.
 *
 * This middleware caches query results for queries that either implement
 * the Cacheable contract or are marked with the Cacheable attribute.
 * The contract takes priority over the attribute.
 */
final class CacheMiddleware implements Middleware
{
    /**
     * Handle the action.
     *
     * @template TReturn
     *
     * @param  Action<TReturn>  $action  The action to handle
     * @param  Closure  $next  The next middleware in the chain
     * @return TReturn The result of the action
     */
    public function handle(Action $action, Closure $next): mixed
    {
        $prefix = config('actions.cache.prefix', 'action');
        $cacheConfig = $this->resolveCacheConfig($action, $prefix);

        if ($cacheConfig === null) {
            return $next($action);
        }

        [$cacheKey, $ttl] = $cacheConfig;

        $freshTtl = $ttl;
        $storageTtl = $ttl * 3;
        $callback = static fn () => $next($action);
        $lock = ['seconds' => 10, 'owner' => uniqid('query_cache_', true)];

        if (! app()->environment('testing') && method_exists(Cache::getStore(), 'tags')) {
            return Cache::tags([$prefix, get_class($action)])
                ->flexible($cacheKey, [$freshTtl, $storageTtl], $callback, $lock);
        }

        return Cache::flexible($cacheKey, [$freshTtl, $storageTtl], $callback, $lock);
    }

    /**
     * Resolve cache configuration from the action.
     *
     * Priority: Cacheable contract > #[Cacheable] attribute > null (skip).
     *
     * @template TReturn
     *
     * @param  Action<TReturn>  $action
     * @return array{0: string, 1: int}|null [cacheKey, ttl] or null if not cacheable
     */
    private function resolveCacheConfig(Action $action, string $prefix): ?array
    {
        if ($action instanceof CacheableContract) {
            return [$action->cacheKey(), $action->cacheTtl()];
        }

        $attribute = $this->getCacheableAttribute($action);

        if (! $attribute instanceof Cacheable) {
            return null;
        }

        return [$this->generateCacheKey($action, $attribute, $prefix), $attribute->ttl];
    }

    /**
     * Get the Cacheable attribute from an action.
     *
     * @template TReturn
     *
     * @param  Action<TReturn>  $action  The action to inspect
     * @return Cacheable|null The Cacheable attribute if found, null otherwise
     */
    private function getCacheableAttribute(Action $action): ?object
    {
        $reflection = new ReflectionClass($action);
        $attributes = $reflection->getAttributes(Cacheable::class);

        if ($attributes === []) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    /**
     * Generate a cache key for an action.
     *
     * @template TReturn
     *
     * @param  Action<TReturn>  $action  The action to generate a key for
     * @param  Cacheable  $attribute  The cacheable attribute
     * @return string The cache key
     */
    private function generateCacheKey(Action $action, Cacheable $attribute, string $prefix): string
    {
        if ($attribute->key !== null) {
            return "{$prefix}:{$attribute->key}";
        }

        $actionClass = get_class($action);
        $actionParams = $this->serializeActionParams($action);
        $hash = md5($actionParams);

        return "{$prefix}:{$actionClass}:{$hash}";
    }

    /**
     * Serialize action parameters for cache key generation.
     *
     * @template TReturn
     *
     * @param  Action<TReturn>  $action  The action to serialize
     * @return string Serialized action params
     */
    private function serializeActionParams(Action $action): string
    {
        $params = [];
        $reflection = new ReflectionClass($action);

        foreach ($reflection->getProperties() as $property) {
            if (! $property->isPublic()) {
                continue;
            }

            $name = $property->getName();
            $value = $property->getValue($action);

            if (is_object($value) && method_exists($value, 'getKey')) {
                $value = get_class($value).':'.$value->getKey();
            } elseif (is_object($value)) {
                $value = get_class($value);
            }

            $params[$name] = $value;
        }

        return serialize($params);
    }
}
