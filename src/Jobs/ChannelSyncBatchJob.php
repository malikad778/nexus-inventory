<?php

namespace Adnan\LaravelNexus\Jobs;

use Adnan\LaravelNexus\Events\AfterInventorySync;
use Adnan\LaravelNexus\Events\BeforeInventorySync;
use Adnan\LaravelNexus\Events\InventorySyncFailed;
use Adnan\LaravelNexus\Models\ChannelMapping;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ChannelSyncBatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $channel)
    {
        //
    }

    public $tries = 3;

    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        // 1. Dispatch Before Sync Event
        BeforeInventorySync::dispatch($this->channel, $this->batch()?->id);

        $processedCount = 0;

        // 2. Iterate local mappings and push to channel
        // We assume 'syncable' has a 'quantity' attribute.
        // In a real app, this might use a contract or configuration.
        ChannelMapping::where('channel', $this->channel)
            ->with('syncable')
            ->chunk(100, function ($mappings) use (&$processedCount) {
                foreach ($mappings as $mapping) {
                    if (isset($mapping->syncable?->quantity)) {
                        PushInventoryJob::dispatch(
                            $this->channel,
                            $mapping->remote_id,
                            (int) $mapping->syncable->quantity
                        );
                        $processedCount++;
                    }
                }
            });

        // 3. Dispatch After Sync Event
        AfterInventorySync::dispatch($this->channel, $processedCount, $this->batch()?->id);
    }

    public function failed(Throwable $exception): void
    {
        InventorySyncFailed::dispatch(
            $this->channel,
            $exception->getMessage(),
            $this->batch()?->id
        );
    }
}
