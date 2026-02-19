<?php

namespace Adnan\LaravelNexus\DataTransferObjects;

class NexusInventoryUpdate
{
    public function __construct(
        public string $sku,
        public int $quantity,
        public ?string $remoteId = null,
        public array $meta = []
    ) {}
}
