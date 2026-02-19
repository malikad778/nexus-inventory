<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Adnan\LaravelNexus\Events\WebhookReceived;
use Adnan\LaravelNexus\Http\Controllers\WebhookController;

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

        $payload = json_encode(['foo' => 'bar']);
        // Hash content as string
        $signature = base64_encode(hash_hmac('sha256', $payload, 'secret123', true));

        $response = $this->postJson('test-webhooks/shopify', ['foo' => 'bar'], [
            'X-Shopify-Hmac-Sha256' => $signature,
            'X-Shopify-Topic' => 'products/update',
        ]);

        $response->assertOk();

        // Check DB Log
        $log = DB::table('nexus_webhook_logs')->first();
        expect($log)->not->toBeNull();
        expect($log->channel)->toBe('shopify');
        expect($log->topic)->toBe('products/update');
        expect($log->status)->toBe('pending');
        
        // Check Event
        Event::assertDispatched(WebhookReceived::class, function ($event) use ($log) {
            return $event->channel === 'shopify' &&
                   $event->payload['foo'] === 'bar' &&
                   $event->logId === $log->id;
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
