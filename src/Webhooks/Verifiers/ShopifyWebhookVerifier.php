<?php

namespace Malikad778\LaravelNexus\Webhooks\Verifiers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Malikad778\LaravelNexus\Contracts\WebhookVerifier;

class ShopifyWebhookVerifier implements WebhookVerifier
{
    public function verify(Request $request): bool
    {
        $signature = $request->header('X-Shopify-Hmac-Sha256');
        $secret = Config::get('nexus.drivers.shopify.access_token'); 

        
        
        
        $secret = Config::get('nexus.drivers.shopify.webhook_secret') ?? Config::get('nexus.drivers.shopify.client_secret');

        if (! $signature || ! $secret) {
            return false;
        }

        $calculated = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        return hash_equals($signature, $calculated);
    }
}
