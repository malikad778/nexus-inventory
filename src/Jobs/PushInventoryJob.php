<?php

namespace Adnan\LaravelNexus\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Adnan\LaravelNexus\Facades\Nexus;
use Adnan\LaravelNexus\RateLimiting\TokenBucket;

class PushInventoryJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(
        public string $channel,
        public string $remoteId,
        public int $quantity
    ) {
        //
    }

    public function uniqueId(): string
    {
        return "{$this->channel}:{$this->remoteId}";
    }

    public function handle(TokenBucket $limiter): void
    {
        // Rate Limiting Check
        // Capacity 10, Rate 1 token/sec (10 requests burst, 1 req/s sustained)
        // Configuration should ideally come from config()
        $capacity = config("nexus.rate_limits.{$this->channel}.capacity", 10);
        $rate = config("nexus.rate_limits.{$this->channel}.rate", 1.0);

        if (! $limiter->acquire($this->channel, $capacity, $rate)) {
            $this->release(5); // Release back to queue with delay
            return;
        }

        $driver = Nexus::driver($this->channel);
        
        if (! $driver->updateInventory($this->remoteId, $this->quantity)) {
            $this->fail(new \Exception("Failed to update inventory for {$this->channel}: {$this->remoteId}"));
        }
    }
}
