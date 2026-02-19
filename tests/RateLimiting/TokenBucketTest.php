<?php

use Illuminate\Support\Facades\Redis;
use Adnan\LaravelNexus\RateLimiting\TokenBucket;

it('can acquire tokens', function () {
    // Mock Redis::eval
    Redis::shouldReceive('eval')
        ->once()
        ->withArgs(function ($script, $numKeys, $key, $capacity, $rate, $cost, $time) {
            return $key === 'nexus:limiter:test_channel' &&
                   $capacity === 10 &&
                   $rate === 1.0 &&
                   $cost === 1;
        })
        ->andReturn(1);

    $bucket = new TokenBucket();
    $result = $bucket->acquire('test_channel', 10, 1.0);

    expect($result)->toBeTrue();
});

it('fails when redis returns 0', function () {
    Redis::shouldReceive('eval')
        ->once()
        ->andReturn(0);

    $bucket = new TokenBucket();
    $result = $bucket->acquire('test_channel', 10, 1.0);

    expect($result)->toBeFalse();
});
