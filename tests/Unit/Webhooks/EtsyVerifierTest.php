<?php

namespace Adnan\LaravelNexus\Tests\Unit\Webhooks;

use Adnan\LaravelNexus\Webhooks\Verifiers\EtsyWebhookVerifier;
use Illuminate\Http\Request;



it('verifies valid etsy signature', function () {
    $config = ['webhook_secret' => 'secret123'];
    $verifier = new EtsyWebhookVerifier($config);
    
    $payload = '{"foo":"bar"}';
    $signature = hash_hmac('sha256', $payload, 'secret123'); // Hex hash for Etsy usually? 
    // Wait, Etsy V3 doesn't standardly use HMAC-SHA256 in all docs, but for this audit we assumed it.
    // Let's stick to the implementation: hash_hmac directly (hex output default).
    
    $request = Request::create('/msg', 'POST', [], [], [], [], $payload);
    $request->headers->set('X-Etsy-Signature', $signature);
    
    expect($verifier->verify($request))->toBeTrue();
});

it('rejects invalid etsy signature', function () {
    $config = ['webhook_secret' => 'secret123'];
    $verifier = new EtsyWebhookVerifier($config);
    
    $payload = '{"foo":"bar"}';
    
    $request = Request::create('/msg', 'POST', [], [], [], [], $payload);
    $request->headers->set('X-Etsy-Signature', 'invalid');
    
    expect($verifier->verify($request))->toBeFalse();
});
