<?php

namespace Malikad778\LaravelNexus\Webhooks\Verifiers;

use Illuminate\Http\Request;
use Malikad778\LaravelNexus\Contracts\WebhookVerifier;

class WooCommerceWebhookVerifier implements WebhookVerifier
{
    public function __construct(protected array $config) {}

    public function verify(Request $request): bool
    {
        $signature = $request->header('x-wc-webhook-signature');
        $secret = $this->config['webhook_secret'] ?? $this->config['client_secret'] ?? '';

        if (empty($signature) || empty($secret)) {
            return false;
        }

        $calculated = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        return hash_equals($signature, $calculated);
    }
}
