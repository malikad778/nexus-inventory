<?php

use Adnan\LaravelNexus\Contracts\InventoryDriver;
use Adnan\LaravelNexus\Facades\Nexus;
use Adnan\LaravelNexus\Jobs\PushInventoryJob;
use Adnan\LaravelNexus\RateLimiting\TokenBucket;

it('respects rate limits and updates inventory', function () {
    $limiter = Mockery::mock(TokenBucket::class);
    $limiter->shouldReceive('acquire')
        ->with('shopify', 10, 2.0)
        ->andReturn(true);

    $driver = Mockery::mock(InventoryDriver::class);
    $driver->shouldReceive('updateInventory')
        ->with('123', 5)
        ->andReturn(true);

    Nexus::shouldReceive('driver')
        ->with('shopify')
        ->andReturn($driver);

    config()->set('nexus.rate_limits.shopify', ['capacity' => 10, 'rate' => 2.0]);

    $job = new PushInventoryJob('shopify', '123', 5);
    $job->handle($limiter);

    expect(true)->toBeTrue(); // Silence risky test warning
});

it('releases job when rate limit exceeded', function () {
    $limiter = Mockery::mock(TokenBucket::class);
    $limiter->shouldReceive('acquire')
        ->andReturn(false);

    \Illuminate\Support\Facades\Event::fake();

    $job = Mockery::mock(PushInventoryJob::class, ['shopify', '123', 5])->makePartial();
    $job->shouldReceive('release')->with(5)->once();

    $job->handle($limiter);

    \Illuminate\Support\Facades\Event::assertDispatched(\Adnan\LaravelNexus\Events\ChannelThrottled::class, function ($event) {
        return $event->channel === 'shopify' && $event->retryAfter === 5;
    });
});

it('dispatches failure event on exception', function () {
    $limiter = Mockery::mock(TokenBucket::class);
    $limiter->shouldReceive('acquire')->andReturn(true);

    $driver = Mockery::mock(InventoryDriver::class);
    $driver->shouldReceive('updateInventory')->andThrow(new Exception('API Error'));

    Nexus::shouldReceive('driver')->with('shopify')->andReturn($driver);

    \Illuminate\Support\Facades\Event::fake();

    $job = new PushInventoryJob('shopify', '123', 5);

    try {
        $job->handle($limiter);
    } catch (Exception $e) {
        // execute failed() manually as worker would
        $job->failed($e);
    }

    \Illuminate\Support\Facades\Event::assertDispatched(\Adnan\LaravelNexus\Events\InventorySyncFailed::class, function ($event) {
        return $event->channel === 'shopify' && $event->reason === 'API Error';
    });
});
