<?php

namespace Malikad778\LaravelNexus\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class CatalogSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  \Illuminate\Database\Eloquent\Collection|null  $products
     *         An Eloquent collection of syncable models (must have a `quantity`
     *         attribute and a `channelMappings` relationship).  When null the job
     *         falls back to a full-catalog sync via ChannelSyncBatchJob.
     * @param  array  $channels  Limit the sync to these channels (empty = all configured).
     */
    public function __construct(
        public readonly ?Collection $products = null,
        public readonly array $channels = [],
    ) {}

    public function handle(): void
    {
        $channels = ! empty($this->channels)
            ? $this->channels
            : array_keys(config('nexus.drivers', []));

        // --- Product-collection path: partition and dispatch per SKU/channel pair ---
        if ($this->products !== null) {
            $batch = [];

            foreach ($this->products as $product) {
                foreach ($product->channelMappings as $mapping) {
                    if (! in_array($mapping->channel, $channels, true)) {
                        continue;
                    }

                    if (! isset($product->quantity)) {
                        continue;
                    }

                    $batch[] = new PushInventoryJob(
                        channel: $mapping->channel,
                        remoteId: $mapping->remote_id,
                        quantity: (int) $product->quantity,
                    );
                }
            }

            if (! empty($batch)) {
                Bus::batch($batch)
                    ->name('nexus-catalog-sync')
                    ->allowFailures()
                    ->dispatch();
            }

            return;
        }

        // --- Fallback: full DB-driven sync via ChannelSyncBatchJob ---
        $batch = array_map(
            fn (string $channel) => new ChannelSyncBatchJob($channel),
            $channels,
        );

        if (! empty($batch)) {
            Bus::batch($batch)
                ->name('nexus-catalog-sync')
                ->allowFailures()
                ->dispatch();
        }
    }
}
