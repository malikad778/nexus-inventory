<?php

namespace Adnan\LaravelNexus\Events;

use Adnan\LaravelNexus\DataTransferObjects\NexusInventoryUpdate;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $channel,
        public NexusInventoryUpdate $update,
        public bool $success
    ) {}
}
