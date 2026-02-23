<?php

namespace Malikad778\LaravelNexus\Contracts;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Malikad778\LaravelNexus\DataTransferObjects\NexusInventoryUpdate;
use Malikad778\LaravelNexus\DataTransferObjects\NexusProduct;
use Malikad778\LaravelNexus\DataTransferObjects\RateLimitConfig;

interface InventoryDriver
{
    

    public function getProducts(Carbon $since): Collection;

    public function fetchProduct(string $remoteId): NexusProduct;

    

    public function updateInventory(string $remoteId, int $quantity): bool;

    public function pushInventory(NexusInventoryUpdate $update): bool;

    public function verifyWebhookSignature(Request $request): bool;

    public function getWebhookVerifier(): WebhookVerifier;

    public function extractWebhookTopic(Request $request): string;

    public function parseWebhookPayload(Request $request): NexusInventoryUpdate;

    public function getRateLimitConfig(): RateLimitConfig;

    

    public function getChannelName(): string;
}
