<?php

namespace Adnan\LaravelNexus\Webhooks\Verifiers;

use Adnan\LaravelNexus\Contracts\WebhookVerifier;
use Illuminate\Http\Request;

class EtsyWebhookVerifier implements WebhookVerifier
{
    public function __construct(protected array $config) {}

    public function verify(Request $request): bool
    {
        // Etsy v3 standard webhook verification involves checking the `X-Etsy-Signature` header
        // which is an HMAC-SHA256 hash of the payload using the keystring (client_id) + shared secret?
        // Actually, Etsy V3 documentation indicates verifying the signature against the `keystring`.

        // HOWEVER, many Etsy integrations use a random secret set during webhook creation.
        // We will assume a 'webhook_secret' is configured.

        $signature = $request->header('X-Etsy-Signature');
        $secret = $this->config['webhook_secret'] ?? ''; // Etsy specific webhook secret

        if (empty($signature) || empty($secret)) {
            // Log warning or fail? For security, just fail.
            return false;
        }

        // Verify HMAC SHA256
        $calculated = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($signature, $calculated);
    }
}
