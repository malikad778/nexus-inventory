<?php

namespace Adnan\LaravelNexus\Webhooks\Verifiers;

use Adnan\LaravelNexus\Contracts\WebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class ShopifyWebhookVerifier implements WebhookVerifier
{
    public function verify(Request $request): bool
    {
        $signature = $request->header('X-Shopify-Hmac-Sha256');
        $secret = Config::get('nexus.drivers.shopify.access_token'); // Usually it's a separate WEBHOOK_SECRET, but leveraging existing config or env.

        // Spec check: Does Shopify use Access Token or Client Secret for webhooks?
        // It uses "Shared Secret" (Client Secret) or a specific Webhook Signing Key.
        // Let's assume we add a 'webhook_secret' to config key.
        $secret = Config::get('nexus.drivers.shopify.webhook_secret') ?? Config::get('nexus.drivers.shopify.client_secret');

        if (! $signature || ! $secret) {
            return false;
        }

        $calculated = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        return hash_equals($signature, $calculated);
    }
}
