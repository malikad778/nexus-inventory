<?php

namespace Adnan\LaravelNexus\Contracts;

use Illuminate\Support\Enumerable;
use Illuminate\Support\Carbon;

interface InventoryDriver
{
    /**
     * Fetch products modified since the given timestamp.
     * 
     * @return Enumerable<int, \Adnan\LaravelNexus\DataTransferObjects\NexusProduct>
     */
    public function getProducts(Carbon $since): Enumerable;

    /**
     * Update inventory for a specific product variant.
     */
    public function updateInventory(string $remoteId, int $quantity): bool;

    /**
     * Get the unique identifier for the channel (e.g., 'shopify', 'woocommerce').
     */
    public function getChannelName(): string;
}
