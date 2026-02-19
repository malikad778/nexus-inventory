<?php

namespace Adnan\LaravelNexus\Events;

use Adnan\LaravelNexus\DataTransferObjects\NexusProduct;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductImported
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $channel,
        public NexusProduct $product
    ) {}
}
