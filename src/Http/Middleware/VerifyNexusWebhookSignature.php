<?php

namespace Adnan\LaravelNexus\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Adnan\LaravelNexus\Webhooks\Verifiers\ShopifyWebhookVerifier;
use Adnan\LaravelNexus\Webhooks\Verifiers\AmazonWebhookVerifier;

class VerifyNexusWebhookSignature
{
    public function handle(Request $request, Closure $next, string $channel = null): Response
    {
        // Loopback/Testing bypass if needed (optional)
        
        if (! $channel) {
            // Try to guess from route param
            $channel = $request->route('channel');
        }

        $verifier = $this->resolveVerifier($channel);

        if ($verifier && ! $verifier->verify($request)) {
             \Illuminate\Support\Facades\DB::table('nexus_webhook_logs')->insert([
                'channel' => $channel,
                'topic' => $request->header('X-Shopify-Topic') 
                    ?? $request->header('X-GitHub-Event') 
                    ?? $request->json('Type') 
                    ?? 'unknown',
                'payload' => json_encode($request->all()),
                'headers' => json_encode($request->headers->all()),
                'status' => 'failed',
                'exception' => 'Invalid webhook signature.',
                'created_at' => now(),
                'updated_at' => now(),
             ]);

             throw new AccessDeniedHttpException('Invalid webhook signature.');
        }

        return $next($request);
    }

    protected function resolveVerifier(?string $channel): ?\Adnan\LaravelNexus\Webhooks\Verifiers\WebhookVerifier
    {
        return match ($channel) {
            'shopify' => new ShopifyWebhookVerifier(),
            'amazon' => new AmazonWebhookVerifier(),
            // 'woocommerce' => new WooCommerceWebhookVerifier(),
            // 'etsy' => new EtsyWebhookVerifier(),
            default => null,
        };
    }
}
