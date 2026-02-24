<?php

namespace Malikad778\LaravelNexus\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Malikad778\LaravelNexus\Events\ChannelThrottled;
use Malikad778\LaravelNexus\Events\InventorySyncFailed;
use Malikad778\LaravelNexus\Events\InventoryUpdated;
use Malikad778\LaravelNexus\Facades\Nexus;
use Malikad778\LaravelNexus\RateLimiting\TokenBucket;

class PushInventoryJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    public function __construct(
        public string $channel,
        public string $remoteId,
        public int $quantity
    ) {
        
    }

    public function uniqueId(): string
    {
        return "{$this->channel}:{$this->remoteId}";
    }

    public function handle(TokenBucket $limiter): void
    {
        
        
        
        $capacity = config("nexus.rate_limits.{$this->channel}.capacity", 10);
        $rate = config("nexus.rate_limits.{$this->channel}.rate", 1.0);

        if (! $limiter->acquire($this->channel, $capacity, $rate)) {
            ChannelThrottled::dispatch($this->channel, 5);
            $this->release(5); 

            return;
        }

        $driver = Nexus::driver($this->channel);

        
        try {
            $product = $driver->fetchProduct($this->remoteId);
            $previousQuantity = $product->quantity;
        } catch (\Exception $e) {
            $product = null;
            $previousQuantity = 0;
        }

        if ($driver->updateInventory($this->remoteId, $this->quantity)) {
            // Stamp last_synced_at on the channel mapping so the dashboard can show
            // per-channel freshness data without a separate query.
            \Malikad778\LaravelNexus\Models\ChannelMapping::where('channel', $this->channel)
                ->where('remote_id', $this->remoteId)
                ->update(['last_synced_at' => now()]);

            if ($product) {
                InventoryUpdated::dispatch(
                    $this->channel,
                    $product,
                    $previousQuantity,
                    $this->quantity
                );
            }
        } else {
            $this->fail(new \Exception("Failed to update inventory for {$this->channel}: {$this->remoteId}"));
        }
    }

    public function failed(\Throwable $exception): void
    {
        InventorySyncFailed::dispatch(
            $this->channel,
            $exception->getMessage()
        );
    }
}
