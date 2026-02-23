<?php

use Illuminate\Support\Facades\Bus;
use Malikad778\LaravelNexus\Jobs\CatalogSyncJob;
use Malikad778\LaravelNexus\Jobs\ChannelSyncBatchJob;

it('dispatches batch job for each channel', function () {
    config()->set('nexus.drivers', [
        'shopify' => [],
        'woocommerce' => [],
    ]);

    Bus::fake();

    (new CatalogSyncJob)->handle();

    Bus::assertBatched(function ($batch) {
        return $batch->name === 'nexus-catalog-sync' &&
               $batch->jobs->count() === 2 &&
               $batch->jobs->first() instanceof ChannelSyncBatchJob;
    });
});
