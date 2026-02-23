<?php

namespace Malikad778\LaravelNexus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AfterInventorySync
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $channel,
        public int $productsSynced,
        public ?string $batchId = null
    ) {}
}
