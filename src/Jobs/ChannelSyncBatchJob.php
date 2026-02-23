<?php

namespace Malikad778\LaravelNexus\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Malikad778\LaravelNexus\Events\AfterInventorySync;
use Malikad778\LaravelNexus\Events\BeforeInventorySync;
use Malikad778\LaravelNexus\Events\InventorySyncFailed;
use Malikad778\LaravelNexus\Models\ChannelMapping;
use Throwable;

class ChannelSyncBatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $channel)
    {
        
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

        
        BeforeInventorySync::dispatch($this->channel, $this->batch()?->id);

        $processedCount = 0;

        
        
        
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
