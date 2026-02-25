<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Middleware;

use Closure;
use ShafiMsp\Actions\Attributes\Cacheable;
use ShafiMsp\Actions\Contracts\Action;
use ShafiMsp\Actions\Contracts\Middleware;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;

/**
 * Middleware to cache query results.
 *
 * This middleware caches query results for queries marked with the Cacheable attribute.
 * The cache key is generated based on the query class and its parameters.
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
        $cacheableAttribute = $this->getCacheableAttribute($action);

        if (! $cacheableAttribute instanceof Cacheable) {
            return $next($action);
        }

        $cacheKey = $this->generateCacheKey($action, $cacheableAttribute);
        $ttl = $cacheableAttribute->ttl;

        $freshTtl = $ttl;
        $storageTtl = $ttl * 3;
        $callback = static fn () => $next($action);
        $lock = ['seconds' => 10, 'owner' => uniqid('query_cache_', true)];

        if (! app()->environment('testing') && method_exists(Cache::getStore(), 'tags')) {
            return Cache::tags(['query', get_class($action)])
                ->flexible($cacheKey, [$freshTtl, $storageTtl], $callback, $lock);
        }

        return Cache::flexible($cacheKey, [$freshTtl, $storageTtl], $callback, $lock);
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
    private function generateCacheKey(Action $action, Cacheable $attribute): string
    {
        if ($attribute->key !== null) {
            return "query:{$attribute->key}";
        }

        $actionClass = get_class($action);
        $actionParams = $this->serializeActionParams($action);
        $hash = md5($actionParams);

        return "query:{$actionClass}:{$hash}";
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
