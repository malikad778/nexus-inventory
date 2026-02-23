<?php

namespace Malikad778\LaravelNexus\Webhooks\Verifiers;

use Illuminate\Http\Request;
use Malikad778\LaravelNexus\Contracts\WebhookVerifier;

class EtsyWebhookVerifier implements WebhookVerifier
{
    public function __construct(protected array $config) {}

    public function verify(Request $request): bool
    {
        
        
        

        
        

        $signature = $request->header('X-Etsy-Signature');
        $secret = $this->config['webhook_secret'] ?? ''; 

        if (empty($signature) || empty($secret)) {
            
            return false;
        }

        
        $calculated = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($signature, $calculated);
    }
}
