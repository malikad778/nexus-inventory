<?php

namespace Malikad778\LaravelNexus\Drivers\Shopify;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Malikad778\LaravelNexus\Contracts\InventoryDriver;
use Malikad778\LaravelNexus\DataTransferObjects\NexusInventoryUpdate;
use Malikad778\LaravelNexus\DataTransferObjects\NexusProduct;
use Malikad778\LaravelNexus\DataTransferObjects\RateLimitConfig;

class ShopifyDriver implements InventoryDriver
{
    protected string $baseUrl;

    public function __construct(protected array $config)
    {
        $this->baseUrl = "https://{$this->config['shop_url']}/admin/api/".($this->config['api_version'] ?? '2024-01');
    }

    public function getProducts(Carbon $since): Collection
    {
        $response = Http::withHeaders($this->getHeaders())
            ->get("{$this->baseUrl}/products.json", [
                'updated_at_min' => $since->toIso8601String(),
                'limit' => 250,
            ]);

        if ($response->failed()) {
            $response->throw();
        }

        return collect($response->json('products', []))
            ->map(fn (array $product) => NexusProduct::fromShopify($product));
    }

    public function fetchProduct(string $remoteId): NexusProduct
    {
        $response = Http::withHeaders($this->getHeaders())
            ->get("{$this->baseUrl}/products/{$remoteId}.json");

        if ($response->failed()) {
            $response->throw();
        }

        return NexusProduct::fromShopify($response->json('product'));
    }

    public function updateInventory(string $remoteId, int $quantity): bool
    {
        
        
        $variantResponse = Http::withHeaders($this->getHeaders())
            ->get("{$this->baseUrl}/variants/{$remoteId}.json");

        if ($variantResponse->failed()) {
            return false;
        }

        $inventoryItemId = $variantResponse->json('variant.inventory_item_id');

        if (! $inventoryItemId) {
            return false;
        }

        
        $response = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl}/inventory_levels/set.json", [
                'location_id' => $this->config['location_id'],
                'inventory_item_id' => $inventoryItemId,
                'available' => $quantity,
            ]);

        return $response->successful();
    }

    public function pushInventory(NexusInventoryUpdate $update): bool
    {
        
        
        $remoteId = $update->remoteId;

        
        if (! $remoteId) {
            
            return false;
        }

        return $this->updateInventory($remoteId, $update->quantity);
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return $this->getWebhookVerifier()->verify($request);
    }

    public function extractWebhookTopic(Request $request): string
    {
        return $request->header('X-Shopify-Topic') ?? 'unknown';
    }

    public function getWebhookVerifier(): \Malikad778\LaravelNexus\Contracts\WebhookVerifier
    {
        return new \Malikad778\LaravelNexus\Webhooks\Verifiers\ShopifyWebhookVerifier;
    }

    public function parseWebhookPayload(Request $request): NexusInventoryUpdate
    {
        $payload = $request->json()->all();

        
        
        $sku = $payload['sku'] ?? $payload['variants'][0]['sku'] ?? 'unknown';
        $qty = $payload['inventory_quantity'] ?? $payload['variants'][0]['inventory_quantity'] ?? 0;
        $id = (string) ($payload['id'] ?? '');

        return new NexusInventoryUpdate(
            sku: $sku,
            quantity: (int) $qty,
            remoteId: $id,
            meta: $payload
        );
    }

    public function getRateLimitConfig(): RateLimitConfig
    {
        return new RateLimitConfig(
            capacity: 40,
            rate: 2, 
            cost: 1
        );
    }

    public function getChannelName(): string
    {
        return 'shopify';
    }

    protected function getHeaders(): array
    {
        return [
            'X-Shopify-Access-Token' => $this->config['access_token'] ?? '',
        ];
    }
}
