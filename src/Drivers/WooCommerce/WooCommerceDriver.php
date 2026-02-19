<?php

namespace Adnan\LaravelNexus\Drivers\WooCommerce;

use Adnan\LaravelNexus\Contracts\InventoryDriver;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Adnan\LaravelNexus\DataTransferObjects\NexusProduct;

class WooCommerceDriver implements InventoryDriver
{
    public function __construct(protected array $config) {}

    public function getProducts(Carbon $since): Enumerable
    {
        $response = Http::withBasicAuth(
            $this->config['consumer_key'],
            $this->config['consumer_secret']
        )->get("{$this->config['store_url']}/wp-json/wc/v3/products", [
            'modified_after' => $since->toIso8601String(),
            'per_page' => 100, 
        ]);

        if ($response->failed()) {
            $response->throw();
        }

        return collect($response->json())
            ->map(function ($product) {
                // If product is variable, we might need to fetch variations separately
                // For simplicity, let's assume simple products for now or main product
                // But NexusProduct expects "sku". 
                
                // Note: Variations handling for 'variable' type is not yet implemented.
                if (($product['type'] ?? 'simple') === 'variable') {
                    // Log::warning("Variable product {$product['id']} encountered in WooCommerce. Variations not fully supported.");
                }
                
                return new NexusProduct(
                    sku: $product['sku'] ?? '',
                    name: $product['name'],
                    price: (float) $product['price'],
                    quantity: (int) ($product['stock_quantity'] ?? 0),
                    remote_id: (string) $product['id'],
                    remote_data: $product,
                );
            });
    }

    public function updateInventory(string $remoteId, int $quantity): bool
    {
        $response = Http::withBasicAuth(
            $this->config['consumer_key'],
            $this->config['consumer_secret']
        )->put("{$this->config['store_url']}/wp-json/wc/v3/products/{$remoteId}", [
            'manage_stock' => true,
            'stock_quantity' => $quantity,
        ]);

        return $response->successful();
    }

    public function getChannelName(): string
    {
        return 'woocommerce';
    }
}
