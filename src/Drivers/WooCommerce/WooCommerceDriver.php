<?php

namespace Adnan\LaravelNexus\Drivers\WooCommerce;

use Adnan\LaravelNexus\Contracts\InventoryDriver;
use Adnan\LaravelNexus\DataTransferObjects\NexusInventoryUpdate;
use Adnan\LaravelNexus\DataTransferObjects\NexusProduct;
use Adnan\LaravelNexus\DataTransferObjects\RateLimitConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class WooCommerceDriver implements InventoryDriver
{
    protected string $baseUrl;

    public function __construct(protected array $config)
    {
        $this->baseUrl = rtrim($this->config['store_url'], '/').'/wp-json/wc/v3';
    }

    public function getProducts(Carbon $since): Collection
    {
        $response = Http::withBasicAuth(
            $this->config['consumer_key'],
            $this->config['consumer_secret']
        )->get("{$this->baseUrl}/products", [
            'modified_after' => $since->toIso8601String(),
            'per_page' => 100,
        ]);

        if ($response->failed()) {
            $response->throw();
        }

        return collect($response->json())
            ->map(fn (array $product) => NexusProduct::fromWooCommerce($product));
    }

    public function fetchProduct(string $remoteId): NexusProduct
    {
        $response = Http::withBasicAuth(
            $this->config['consumer_key'],
            $this->config['consumer_secret']
        )->get("{$this->baseUrl}/products/{$remoteId}");

        if ($response->failed()) {
            $response->throw();
        }

        return NexusProduct::fromWooCommerce($response->json());
    }

    public function updateInventory(string $remoteId, int $quantity): bool
    {
        $response = Http::withBasicAuth(
            $this->config['consumer_key'],
            $this->config['consumer_secret']
        )->put("{$this->baseUrl}/products/{$remoteId}", [
            'manage_stock' => true,
            'stock_quantity' => $quantity,
        ]);

        return $response->successful();
    }

    public function pushInventory(NexusInventoryUpdate $update): bool
    {
        if (! $update->remoteId) {
            return false;
        }

        return $this->updateInventory($update->remoteId, $update->quantity);
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return $this->getWebhookVerifier()->verify($request);
    }

    public function getWebhookVerifier(): \Adnan\LaravelNexus\Contracts\WebhookVerifier
    {
        return new \Adnan\LaravelNexus\Webhooks\Verifiers\WooCommerceWebhookVerifier($this->config);
    }

    public function parseWebhookPayload(Request $request): NexusInventoryUpdate
    {
        $payload = $request->json()->all();

        // Basic mapping for product.updated
        $id = (string) ($payload['id'] ?? '');
        $sku = $payload['sku'] ?? '';
        $qty = (int) ($payload['stock_quantity'] ?? 0);

        return new NexusInventoryUpdate(
            sku: $sku,
            quantity: $qty,
            remoteId: $id,
            meta: $payload
        );
    }

    public function getRateLimitConfig(): RateLimitConfig
    {
        // WooCommerce API limit varies by host, but default is often ~25 req/10sec
        return new RateLimitConfig(
            capacity: 20,
            rate: 2,
            cost: 1
        );
    }

    public function getChannelName(): string
    {
        return 'woocommerce';
    }
}
