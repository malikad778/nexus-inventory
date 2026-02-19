<?php

namespace Adnan\LaravelNexus\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Adnan\LaravelNexus\Jobs\ChannelSyncBatchJob;

class CatalogSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $channels = array_keys(config('nexus.drivers', []));

        $batch = [];
        foreach ($channels as $channel) {
            $batch[] = new ChannelSyncBatchJob($channel);
        }

        if (! empty($batch)) {
            Bus::batch($batch)
                ->name('nexus-catalog-sync')
                ->allowFailures()
                ->dispatch();
        }
    }
}
