<?php

namespace Adnan\LaravelNexus\Contracts;

use Adnan\LaravelNexus\DataTransferObjects\NexusInventoryUpdate;
use Adnan\LaravelNexus\DataTransferObjects\NexusProduct;
use Adnan\LaravelNexus\DataTransferObjects\RateLimitConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface InventoryDriver
{
    /**
     * Fetch products modified since the given timestamp.
     *
     * @return Collection<int, NexusProduct>
     */
    public function getProducts(Carbon $since): Collection;

    public function fetchProduct(string $remoteId): NexusProduct;

    /**
     * Update inventory for a specific product variant.
     */
    public function updateInventory(string $remoteId, int $quantity): bool;

    public function pushInventory(NexusInventoryUpdate $update): bool;

    public function verifyWebhookSignature(Request $request): bool;

    public function getWebhookVerifier(): WebhookVerifier;

    public function parseWebhookPayload(Request $request): NexusInventoryUpdate;

    public function getRateLimitConfig(): RateLimitConfig;

    /**
     * Get the unique identifier for the channel (e.g., 'shopify', 'woocommerce').
     */
    public function getChannelName(): string;
}
