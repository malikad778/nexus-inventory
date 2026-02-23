<?php

use Illuminate\Support\Facades\Redis;
use Malikad778\LaravelNexus\RateLimiting\TokenBucket;

it('can acquire tokens', function () {
    
    Redis::shouldReceive('eval')
        ->once()
        ->withArgs(function (string $script, array $args, int $numKeys) {
            return $args[0] === 'nexus:limiter:test_channel' &&
                   $args[1] === '10' &&
                   $args[2] === '1' &&
                   $args[3] === '1' &&
                   $numKeys === 1;
        })
        ->andReturn(1);

    $bucket = new TokenBucket;
    $result = $bucket->acquire('test_channel', 10, 1.0);

    expect($result)->toBeTrue();
});

it('fails when redis returns 0', function () {
    Redis::shouldReceive('eval')
        ->once()
        ->andReturn(0);

    $bucket = new TokenBucket;
    $result = $bucket->acquire('test_channel', 10, 1.0);

    expect($result)->toBeFalse();
});
