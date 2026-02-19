<?php

use Adnan\LaravelNexus\Events\WebhookReceived;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

class WebhookControllerTest extends \Adnan\LaravelNexus\Tests\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register route manually for test
        Route::nexusWebhooks('test-webhooks');
    }

    /** @test */
    public function it_accepts_valid_shopify_webhook()
    {
        Config::set('nexus.drivers.shopify.webhook_secret', 'secret123');
        Event::fake();
        $this->withoutExceptionHandling();

        $payloadData = [
            'id' => 123456,
            'variants' => [
                ['id' => 987654, 'inventory_quantity' => 10, 'sku' => 'SKU-1']
            ]
        ];
        $payload = json_encode($payloadData);
        // Hash content as string
        $signature = base64_encode(hash_hmac('sha256', $payload, 'secret123', true));

        $response = $this->postJson('test-webhooks/shopify', $payloadData, [
            'X-Shopify-Hmac-Sha256' => $signature,
            'X-Shopify-Topic' => 'products/update',
        ]);

        $response->assertOk();

        // Check DB Log
        $log = DB::table('nexus_webhook_logs')->first();
        expect($log)->not->toBeNull();
        expect($log->channel)->toBe('shopify');
        expect($log->topic)->toBe('products/update');
        expect($log->status)->toBe('processed');

        // Check Event
        Event::assertDispatched(WebhookReceived::class, function ($event) use ($log) {
            return $event->channel === 'shopify' &&
                   $event->payload['id'] === 123456 &&
                   $event->logId === $log->id;
        });

        // Check InventoryUpdated Event
        Event::assertDispatched(\Adnan\LaravelNexus\Events\InventoryUpdated::class, function ($event) {
             return $event->channel === 'shopify' &&
                    $event->update->remoteId === '123456';
        });
    }

    /** @test */
    public function it_rejects_invalid_signature_and_logs_failure()
    {
        Config::set('nexus.drivers.shopify.webhook_secret', 'secret123');

        $response = $this->postJson('test-webhooks/shopify', ['foo' => 'bar'], [
            'X-Shopify-Hmac-Sha256' => 'invalid',
            'X-Shopify-Topic' => 'products/update',
        ]);

        $response->assertStatus(403);

        // Assert it was logged as failed
        $log = DB::table('nexus_webhook_logs')->first();
        expect($log)->not->toBeNull();
        expect($log->status)->toBe('failed');
        expect($log->exception)->toContain('Invalid webhook signature');
    }
}
