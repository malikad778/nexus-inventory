<?php

namespace Malikad778\LaravelNexus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Malikad778\LaravelNexus\DataTransferObjects\NexusProduct;

class InventoryUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $channel,
        public NexusProduct $product,
        public int $previousQuantity,
        public int $newQuantity
    ) {}
}
