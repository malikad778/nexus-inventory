<?php

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Malikad778\LaravelNexus\Jobs\PushInventoryJob;

it('logs failed jobs to dlq table', function () {
    
    
    
    

    
    $job = new PushInventoryJob('shopify', '123', 5);
    $job->fail(new \Exception('Test Failure'));

    
    

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

    
    Event::dispatch($event);

    
    $record = DB::table('nexus_dead_letter_queue')->first();

    expect($record)->not->toBeNull();
    expect($record->job_class)->toBe(PushInventoryJob::class);
    expect($record->exception)->toContain('Test Failure');
    expect($record->status)->toBe('failed');
});
