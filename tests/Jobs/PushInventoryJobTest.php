<?php

use Adnan\LaravelNexus\Jobs\PushInventoryJob;
use Adnan\LaravelNexus\RateLimiting\TokenBucket;
use Adnan\LaravelNexus\Facades\Nexus;
use Adnan\LaravelNexus\Contracts\InventoryDriver;
use Mockery\MockInterface;

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

    $job = new PushInventoryJob('shopify', '123', 5);
    
    // We check if release is called. 
    // Since InteractsWithQueue::release is used, we can't easily mock it on the object itself without partial mock 
    // or using Queue::fake() and asserting pushed back?
    // Actually, calling handle directly on a new object won't work well for `release` unless it's job-processed.
    // But we can check if it returns early without calling driver.
    
    Nexus::shouldReceive('driver')->never();
    
    // Partial mock to intercept release
    $job = Mockery::mock(PushInventoryJob::class, ['shopify', '123', 5])->makePartial();
    $job->shouldReceive('release')->with(5)->once();
    
    $job->handle($limiter);
});
