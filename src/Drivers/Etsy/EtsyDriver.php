<?php

namespace Adnan\LaravelNexus\Drivers\Etsy;

use Adnan\LaravelNexus\Contracts\InventoryDriver;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Adnan\LaravelNexus\DataTransferObjects\NexusProduct;

class EtsyDriver implements InventoryDriver
{
    public function __construct(protected array $config) {}

    protected function getAccessToken(): string
    {
        // For Phase 1/2, we assume access token is already available/refreshed via a separate mechanism
        // or passed in config. Implementing full OAuth PKCE flow (redirect, callback, code_verifier)
        // is usually done at the application level (Controllers), not inside the driver's synchronous methods.
        // The driver expects a valid access token or a refresh token to exchange.
        
        // If we have a refresh token, we can exchange it.
        // For simplicity here, we'll assume we might have a refresh token or a valid access token in config/storage.
        
        // Let's implement a simple refresh if access_token is missing but refresh_token exists.
        if (! empty($this->config['access_token'])) {
             return $this->config['access_token'];
        }
        
        if (! empty($this->config['refresh_token'])) {
            $response = Http::post('https://api.etsy.com/v3/public/oauth/token', [
                'grant_type' => 'refresh_token',
                'client_id' => $this->config['client_id'],
                'refresh_token' => $this->config['refresh_token'],
            ]);
            
             if ($response->successful()) {
                 return $response->json('access_token');
             }
             
             // Log the error for debugging
             // Log::error('Etsy Refresh Token Failed: ' . $response->body());
        }

        throw new \RuntimeException('No valid access token available for Etsy. Please Configure ETSY_KEYSTRING and ETSY_REFRESH_TOKEN.');
    }

    public function getProducts(Carbon $since): Enumerable
    {
        $accessToken = $this->getAccessToken();
        
        $response = Http::withHeaders([
            'x-api-key' => $this->config['client_id'],
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get("https://api.etsy.com/v3/application/shops/{$this->config['shop_id']}/listings", [
            'state' => 'active',
            'limit' => 100,
        ]);

        if ($response->failed()) {
            $response->throw();
        }

        return collect($response->json('results', []))
            ->map(function ($listing) {
                return new NexusProduct(
                    sku: $listing['skus'][0] ?? '', // Etsy listings can have multiple SKUs
                    name: $listing['title'],
                    price: (float) ($listing['price']['amount'] / $listing['price']['divisor']),
                    quantity: (int) $listing['quantity'],
                    remote_id: (string) $listing['listing_id'],
                    remote_data: $listing,
                );
            });
    }

    public function updateInventory(string $remoteId, int $quantity): bool
    {
        $accessToken = $this->getAccessToken();

        // Etsy requires specifying which offering/product_id to update, or updating the listing itself.
        // PUT /v3/application/shops/{shop_id}/listings/{listing_id}/inventory
        
        // We need to fetch the listing first to get product_id/offering_id if we want to be precise,
        // but let's assume valid simple listing update for now.
        
        // Construct the body. Etsy inventory update is complex (products -> offerings).
        // Simplified: Update the quantity of the products.
        
        // First, get the inventory to find the product ID (if not stored).
        // For this implementation, let's assume we update only the quantity of requests.
        
        // Real implementation would be:
        // 1. GET /v3/application/listings/{listing_id}/inventory
        // 2. Modify output
        // 3. PUT ...
        
        $inventoryResponse = Http::withHeaders([
            'x-api-key' => $this->config['client_id'],
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get("https://api.etsy.com/v3/application/listings/{$remoteId}/inventory");
        
        if ($inventoryResponse->failed()) {
            return false;
        }
        
        $inventory = $inventoryResponse->json();
        
        // Update quantity for all products (simplified)
        foreach ($inventory['products'] as &$product) {
            foreach ($product['offerings'] as &$offering) {
                $offering['quantity'] = $quantity;
            }
        }
        
        $response = Http::withHeaders([
            'x-api-key' => $this->config['client_id'],
            'Authorization' => 'Bearer ' . $accessToken,
        ])->put("https://api.etsy.com/v3/application/shops/{$this->config['shop_id']}/listings/{$remoteId}/inventory", $inventory);

        return $response->successful();
    }

    public function getChannelName(): string
    {
        return 'etsy';
    }
}
