<?php

namespace Malikad778\LaravelNexus\Tests\Unit;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Malikad778\LaravelNexus\Builders\BatchBuilder;
use Malikad778\LaravelNexus\Builders\CatalogSyncBuilder;
use Malikad778\LaravelNexus\Facades\Nexus;
use Malikad778\LaravelNexus\Jobs\ChannelSyncBatchJob;

it('returns catalog sync builder', function () {
    expect(Nexus::catalog())->toBeInstanceOf(CatalogSyncBuilder::class);
});

it('returns batch builder', function () {
    expect(Nexus::batch())->toBeInstanceOf(BatchBuilder::class);
});

it('catalog builder dispatches batch', function () {
    Bus::fake();

    $batch = Nexus::catalog()
        ->channels(['shopify'])
        ->onQueue('nexus-sync')
        ->sync();

    expect($batch)->toBeInstanceOf(Batch::class);

    Bus::assertBatched(function ($batch) {
        return $batch->name === 'nexus-catalog-sync' &&
               count($batch->jobs) === 1 &&
               $batch->jobs[0] instanceof ChannelSyncBatchJob &&
               $batch->jobs[0]->queue === 'nexus-sync';
    });
});

it('batch builder dispatches batch with custom name', function () {
    Bus::fake();

    Nexus::batch('custom-audit-batch')
        ->add(new ChannelSyncBatchJob('shopify'))
        ->dispatch();

    Bus::assertBatched(function ($batch) {
        return $batch->name === 'custom-audit-batch';
    });
});
