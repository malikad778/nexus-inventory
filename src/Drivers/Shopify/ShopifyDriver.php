<?php

namespace Adnan\LaravelNexus\Drivers\Shopify;

use Adnan\LaravelNexus\Contracts\InventoryDriver;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Adnan\LaravelNexus\DataTransferObjects\NexusProduct;

class ShopifyDriver implements InventoryDriver
{
    public function __construct(protected array $config) {}

    public function getProducts(Carbon $since): Enumerable
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->config['access_token'],
        ])->get("https://{$this->config['shop_url']}/admin/api/{$this->config['api_version']}/products.json", [
            'updated_at_min' => $since->toIso8601String(),
        ]);

        if ($response->failed()) {
            $response->throw();
        }

        return collect($response->json('products', []))
            ->flatMap(function ($product) {
                return collect($product['variants'])->map(function ($variant) use ($product) {
                    return new NexusProduct(
                        sku: $variant['sku'] ?? '',
                        name: $product['title'], // Or variant title if preferred
                        price: (float) $variant['price'],
                        quantity: (int) $variant['inventory_quantity'],
                        remote_id: (string) $variant['id'],
                        remote_data: $variant,
                    );
                });
            });
    }

    public function updateInventory(string $remoteId, int $quantity): bool
    {
        // 1. Get the inventory_item_id from the variant
        $variantResponse = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->config['access_token'],
        ])->get("https://{$this->config['shop_url']}/admin/api/{$this->config['api_version']}/variants/{$remoteId}.json");

        if ($variantResponse->failed()) {
            return false;
        }

        $inventoryItemId = $variantResponse->json('variant.inventory_item_id');

        if (! $inventoryItemId) {
            return false;
        }

        // 2. Set the inventory level
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->config['access_token'],
        ])->post("https://{$this->config['shop_url']}/admin/api/{$this->config['api_version']}/inventory_levels/set.json", [
            'location_id' => $this->config['location_id'],
            'inventory_item_id' => $inventoryItemId,
            'available' => $quantity,
        ]);

        return $response->successful();
    }

    public function getChannelName(): string
    {
        return 'shopify';
    }
}
