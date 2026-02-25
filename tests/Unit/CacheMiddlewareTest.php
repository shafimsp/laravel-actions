<?php

use Illuminate\Support\Facades\Cache;
use ShafiMsp\Actions\Executor;
use ShafiMsp\Actions\Middleware\CacheMiddleware;
use ShafiMsp\Actions\Tests\Fixtures\CacheableContractAction;
use ShafiMsp\Actions\Tests\Fixtures\StringAction;

beforeEach(function () {
    Executor::flushCache();
    Cache::flush();
    $this->executor = new Executor(app());
    $this->executor->pushMiddleware(CacheMiddleware::class);
});

it('caches results using the Cacheable contract key and ttl', function () {
    $action = new CacheableContractAction(value: 'first-call', key: 'my-key', ttl: 300);

    $result1 = $this->executor->execute($action);
    $result2 = $this->executor->execute($action);

    expect($result1)->toBe('first-call');
    expect($result2)->toBe('first-call');
});

it('uses the contract cache key for storage', function () {
    $action = new CacheableContractAction(value: 'stored', key: 'contract-key', ttl: 60);

    $this->executor->execute($action);

    expect(Cache::has('contract-key'))->toBeTrue();
});

it('uses different cache keys for different contract parameters', function () {
    $action1 = new CacheableContractAction(value: 'v1', key: 'key-a', ttl: 60);
    $action2 = new CacheableContractAction(value: 'v2', key: 'key-b', ttl: 60);

    $result1 = $this->executor->execute($action1);
    $result2 = $this->executor->execute($action2);

    expect($result1)->toBe('v1');
    expect($result2)->toBe('v2');
});

it('skips caching for actions without contract or attribute', function () {
    $callCount = 0;
    $this->executor->pushMiddleware(function ($action, $next) use (&$callCount) {
        $callCount++;

        return $next($action);
    });

    $this->executor->execute(new StringAction('a'));
    $this->executor->execute(new StringAction('a'));

    expect($callCount)->toBe(2);
});
