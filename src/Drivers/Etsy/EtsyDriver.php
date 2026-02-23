<?php

namespace Malikad778\LaravelNexus\Drivers\Etsy;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Malikad778\LaravelNexus\Contracts\InventoryDriver;
use Malikad778\LaravelNexus\DataTransferObjects\NexusInventoryUpdate;
use Malikad778\LaravelNexus\DataTransferObjects\NexusProduct;
use Malikad778\LaravelNexus\DataTransferObjects\RateLimitConfig;

class EtsyDriver implements InventoryDriver
{
    public function __construct(protected array $config) {}

    protected function getAccessToken(): string
    {
        
        
        
        

        
        

        
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

            
            
        }

        throw new \RuntimeException('No valid access token available for Etsy. Please Configure ETSY_KEYSTRING and ETSY_REFRESH_TOKEN.');
    }

    public function getProducts(Carbon $since): Collection
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withHeaders([
            'x-api-key' => $this->config['client_id'],
            'Authorization' => 'Bearer '.$accessToken,
        ])->get("https://api.etsy.com/v3/application/shops/{$this->config['shop_id']}/listings", [
            'state' => 'active',
            'limit' => 100,
        ]);

        if ($response->failed()) {
            $response->throw();
        }

        return collect($response->json('results', []))
            ->map(fn (array $listing) => NexusProduct::fromEtsy($listing));
    }

    public function fetchProduct(string $remoteId): NexusProduct
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withHeaders([
            'x-api-key' => $this->config['client_id'],
            'Authorization' => 'Bearer '.$accessToken,
        ])->get("https://api.etsy.com/v3/application/listings/{$remoteId}");

        if ($response->failed()) {
            $response->throw();
        }

        return NexusProduct::fromEtsy($response->json());
    }

    public function updateInventory(string $remoteId, int $quantity): bool
    {
        $accessToken = $this->getAccessToken();

        
        

        
        

        
        

        
        

        
        
        
        

        $inventoryResponse = Http::withHeaders([
            'x-api-key' => $this->config['client_id'],
            'Authorization' => 'Bearer '.$accessToken,
        ])->get("https://api.etsy.com/v3/application/listings/{$remoteId}/inventory");

        if ($inventoryResponse->failed()) {
            return false;
        }

        $inventory = $inventoryResponse->json();

        
        if (isset($inventory['products'])) {
            foreach ($inventory['products'] as &$product) {
                if (isset($product['offerings'])) {
                    foreach ($product['offerings'] as &$offering) {
                        $offering['quantity'] = $quantity;
                    }
                }
            }
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->config['client_id'],
            'Authorization' => 'Bearer '.$accessToken,
        ])->put("https://api.etsy.com/v3/application/shops/{$this->config['shop_id']}/listings/{$remoteId}/inventory", $inventory);

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

    public function extractWebhookTopic(Request $request): string
    {
        return $request->header('X-Etsy-Event') ?? 'unknown';
    }

    public function getWebhookVerifier(): \Malikad778\LaravelNexus\Contracts\WebhookVerifier
    {
        return new \Malikad778\LaravelNexus\Webhooks\Verifiers\EtsyWebhookVerifier($this->config);
    }

    public function parseWebhookPayload(Request $request): NexusInventoryUpdate
    {
        $payload = $request->json()->all();

        
        $id = (string) ($payload['listing_id'] ?? '');
        $qty = (int) ($payload['quantity'] ?? 0); 

        return new NexusInventoryUpdate(
            sku: 'ETSY-'.$id,
            quantity: $qty, 
            remoteId: $id,
            meta: $payload
        );
    }

    public function getRateLimitConfig(): RateLimitConfig
    {
        
        return new RateLimitConfig(
            capacity: 10,
            rate: 2,
            cost: 1
        );
    }

    public function getChannelName(): string
    {
        return 'etsy';
    }
}
