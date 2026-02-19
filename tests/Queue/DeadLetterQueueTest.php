<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Adnan\LaravelNexus\Jobs\PushInventoryJob;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Container\Container;

it('logs failed jobs to dlq table', function () {
    // 1. Setup the environment
    // We need to trigger the event listener in NexusServiceProvider
    // Since we are in a package test, we need to ensure the service provider is booted.
    // Orchestra Testbench handles this in TestCase.
    
    // 2. Create a failing job
    $job = new PushInventoryJob('shopify', '123', 5);
    $job->fail(new \Exception('Test Failure'));
    
    // 3. Fire the JobFailed event manually to simulate queue worker
    // We need a proper Job object wrapper usually, but let's see if we can mock the event payload.
    
    $connectionName = 'database';
    $jobMock = Mockery::mock(\Illuminate\Contracts\Queue\Job::class);
    $jobMock->shouldReceive('resolveName')->andReturn(PushInventoryJob::class);
    $jobMock->shouldReceive('payload')->andReturn(['job' => PushInventoryJob::class, 'data' => []]);
    $jobMock->shouldReceive('attempts')->andReturn(1);
    
    $event = new JobFailed(
        $connectionName,
        $jobMock,
        new \Exception('Test Failure')
    );
    
    // 4. Dispatch the event
    Event::dispatch($event);
    
    // 5. Assert database has the record
    $record = DB::table('nexus_dead_letter_queue')->first();
    
    expect($record)->not->toBeNull();
    expect($record->job_class)->toBe(PushInventoryJob::class);
    expect($record->exception)->toContain('Test Failure');
    expect($record->status)->toBe('failed');
});
