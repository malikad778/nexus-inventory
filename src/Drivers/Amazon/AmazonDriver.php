<?php

namespace Malikad778\LaravelNexus\Drivers\Amazon;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Malikad778\LaravelNexus\Contracts\InventoryDriver;
use Malikad778\LaravelNexus\DataTransferObjects\NexusInventoryUpdate;
use Malikad778\LaravelNexus\DataTransferObjects\NexusProduct;
use Malikad778\LaravelNexus\DataTransferObjects\RateLimitConfig;

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

            return NexusProduct::fromAmazon($item); 
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
            'productType' => 'PRODUCT', 
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
        $content = json_decode($request->getContent(), true);

        return $content['Type'] ?? 'unknown';
    }

    public function getWebhookVerifier(): \Malikad778\LaravelNexus\Contracts\WebhookVerifier
    {
        return new \Malikad778\LaravelNexus\Webhooks\Verifiers\AmazonWebhookVerifier;
    }

    public function parseWebhookPayload(Request $request): NexusInventoryUpdate
    {
        
        $content = json_decode($request->getContent(), true);
        $message = json_decode($content['Message'] ?? '{}', true);

        
        $sku = $message['SellerSKU'] ?? 'unknown';
        $asin = $message['ASIN'] ?? '';

        return new NexusInventoryUpdate(
            sku: $sku,
            quantity: 0, 
            remoteId: $asin,
            meta: $message
        );
    }

    public function getRateLimitConfig(): RateLimitConfig
    {
        
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
