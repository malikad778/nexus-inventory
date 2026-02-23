<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Malikad778\LaravelNexus\Events\WebhookReceived;

class WebhookControllerTest extends \Malikad778\LaravelNexus\Tests\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        
        Route::nexusWebhooks('test-webhooks');
    }

    
    public function it_accepts_valid_shopify_webhook()
    {
        Config::set('nexus.drivers.shopify.webhook_secret', 'secret123');
        Config::set('nexus.drivers.shopify.shop_url', 'test.myshopify.com');
        Event::fake();
        $this->withoutExceptionHandling();

        \Illuminate\Support\Facades\Http::fake([
            '*products/123456.json*' => \Illuminate\Support\Facades\Http::response([
                'product' => [
                    'id' => 123456,
                    'title' => 'Test Product',
                    'variants' => [
                        ['id' => 987654, 'inventory_quantity' => 15, 'sku' => 'SKU-1', 'price' => '10.00'],
                    ],
                ],
            ], 200),
        ]);

        $payloadData = [
            'id' => 123456,
            'variants' => [
                ['id' => 987654, 'inventory_quantity' => 10, 'sku' => 'SKU-1'],
            ],
        ];
        $payload = json_encode($payloadData);
        
        $signature = base64_encode(hash_hmac('sha256', $payload, 'secret123', true));

        $response = $this->postJson('test-webhooks/shopify', $payloadData, [
            'X-Shopify-Hmac-Sha256' => $signature,
            'X-Shopify-Topic' => 'products/update',
        ]);

        $response->assertOk();

        
        $log = DB::table('nexus_webhook_logs')->first();
        expect($log)->not->toBeNull();
        expect($log->channel)->toBe('shopify');
        expect($log->topic)->toBe('products/update');
        expect($log->status)->toBe('processed');

        
        Event::assertDispatched(WebhookReceived::class, function ($event) use ($log) {
            return $event->channel === 'shopify' &&
                   $event->payload['id'] === 123456 &&
                   $event->logId === $log->id;
        });

        
        Event::assertDispatched(\Malikad778\LaravelNexus\Events\InventoryUpdated::class, function ($event) {
            return $event->channel === 'shopify' &&
                   $event->product->id === '123456';
        });
    }

    
    public function it_rejects_invalid_signature_and_logs_failure()
    {
        Config::set('nexus.drivers.shopify.webhook_secret', 'secret123');

        $response = $this->postJson('test-webhooks/shopify', ['foo' => 'bar'], [
            'X-Shopify-Hmac-Sha256' => 'invalid',
            'X-Shopify-Topic' => 'products/update',
        ]);

        $response->assertStatus(403);

        
        $log = DB::table('nexus_webhook_logs')->first();
        expect($log)->not->toBeNull();
        expect($log->status)->toBe('failed');
        expect($log->exception)->toContain('Invalid webhook signature');
    }
}
