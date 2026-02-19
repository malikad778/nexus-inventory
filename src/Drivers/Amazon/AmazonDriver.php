<?php

namespace Adnan\LaravelNexus\Drivers\Amazon;

use Adnan\LaravelNexus\Contracts\InventoryDriver;
use Adnan\LaravelNexus\DataTransferObjects\NexusInventoryUpdate;
use Adnan\LaravelNexus\DataTransferObjects\NexusProduct;
use Adnan\LaravelNexus\DataTransferObjects\RateLimitConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class AmazonDriver implements InventoryDriver
{
    protected ?string $accessToken = null;

    public function __construct(protected array $config) {}

    protected function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $response = Http::post('https://api.amazon.com/auth/o2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->config['refresh_token'],
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to authenticate with Amazon LWA: '.$response->body());
        }

        return $this->accessToken = $response->json('access_token');
    }

    public function getProducts(Carbon $since): Collection
    {
        $accessToken = $this->getAccessToken();

        $endpoint = 'https://sellingpartnerapi-na.amazon.com/catalog/2022-04-01/items';

        $signer = new AwsV4Signer(
            $this->config['access_key_id'],
            $this->config['secret_access_key'],
            $this->config['region'] ?? 'us-east-1'
        );

        $queryParams = [
            'marketplaceIds' => $this->config['marketplace_id'] ?? 'ATVPDKIKX0DER',
            'pageSize' => 20,
            'includedData' => 'summaries,attributes',
        ];

        $urlWithQuery = $endpoint.'?'.http_build_query($queryParams);

        $headers = [
            'x-amz-access-token' => $accessToken,
            'host' => 'sellingpartnerapi-na.amazon.com',
        ];

        $signedHeaders = $signer->signRequest('GET', $urlWithQuery, $headers);

        $response = Http::withHeaders($signedHeaders)->get($urlWithQuery);

        if ($response->failed()) {
            return collect([]);
        }

        $items = $response->json('items', []);

        return collect($items)->map(function ($item) {
            $summary = $item['summaries'][0] ?? [];

            return NexusProduct::fromAmazon($item); // Use the static factory we added in DTO
        });
    }

    public function fetchProduct(string $remoteId): NexusProduct
    {
        $accessToken = $this->getAccessToken();

        $endpoint = "https://sellingpartnerapi-na.amazon.com/catalog/2022-04-01/items/{$remoteId}";

        $queryParams = [
            'marketplaceIds' => $this->config['marketplace_id'] ?? 'ATVPDKIKX0DER',
            'includedData' => 'summaries,attributes,images',
        ];

        $urlWithQuery = $endpoint.'?'.http_build_query($queryParams);

        $signer = new AwsV4Signer(
            $this->config['access_key_id'],
            $this->config['secret_access_key'],
            $this->config['region'] ?? 'us-east-1'
        );

        $headers = [
            'x-amz-access-token' => $accessToken,
            'host' => 'sellingpartnerapi-na.amazon.com',
        ];

        $signedHeaders = $signer->signRequest('GET', $urlWithQuery, $headers);

        $response = Http::withHeaders($signedHeaders)->get($urlWithQuery);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch Amazon product: '.$response->body());
        }

        return NexusProduct::fromAmazon($response->json());
    }

    public function updateInventory(string $remoteId, int $quantity): bool
    {
        $accessToken = $this->getAccessToken();

        $endpoint = "https://sellingpartnerapi-na.amazon.com/listings/2021-08-01/items/{$this->config['seller_id']}/{$remoteId}";

        $body = [
            'productType' => 'PRODUCT', // generic
            'patches' => [
                [
                    'op' => 'replace',
                    'path' => '/attributes/fulfillment_availability',
                    'value' => [
                        [
                            'fulfillment_channel_code' => 'DEFAULT',
                            'quantity' => $quantity,
                        ],
                    ],
                ],
            ],
        ];

        $payload = json_encode($body);

        $signer = new AwsV4Signer(
            $this->config['access_key_id'],
            $this->config['secret_access_key'],
            $this->config['region'] ?? 'us-east-1'
        );

        $headers = [
            'x-amz-access-token' => $accessToken,
            'content-type' => 'application/json',
            'host' => 'sellingpartnerapi-na.amazon.com',
        ];

        $signedHeaders = $signer->signRequest('PATCH', $endpoint, $headers, $payload);

        $response = Http::withHeaders($signedHeaders)
            ->withBody($payload, 'application/json')
            ->patch($endpoint);

        if ($response->failed()) {
            $response->throw();
        }

        return $response->successful();
    }

    public function pushInventory(NexusInventoryUpdate $update): bool
    {
        if (! $update->remoteId) {
            return false; // Amazon usually requires ASIN or SKU
        }

        return $this->updateInventory($update->remoteId, $update->quantity);
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return $this->getWebhookVerifier()->verify($request);
    }

    public function getWebhookVerifier(): \Adnan\LaravelNexus\Contracts\WebhookVerifier
    {
        return new \Adnan\LaravelNexus\Webhooks\Verifiers\AmazonWebhookVerifier($this->config);
    }

    public function parseWebhookPayload(Request $request): NexusInventoryUpdate
    {
        // Parse SNS notification
        $content = json_decode($request->getContent(), true);
        $message = json_decode($content['Message'] ?? '{}', true);

        // Logic to extract SKU/ASIN from common Notification Types (e.g. ListingsItemStatusChange)
        $sku = $message['SellerSKU'] ?? 'unknown';
        $asin = $message['ASIN'] ?? '';

        return new NexusInventoryUpdate(
            sku: $sku,
            quantity: 0, // Often need to fetch fresh
            remoteId: $asin,
            meta: $message
        );
    }

    public function getRateLimitConfig(): RateLimitConfig
    {
        // SP-API is typically 5-10 requests/sec with burst
        return new RateLimitConfig(
            capacity: 5,
            rate: 1,
            cost: 1
        );
    }

    public function getChannelName(): string
    {
        return 'amazon';
    }
}
