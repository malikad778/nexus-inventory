<?php

namespace Malikad778\LaravelNexus\Tests\Unit\Webhooks;

use Illuminate\Http\Request;
use Malikad778\LaravelNexus\Webhooks\Verifiers\EtsyWebhookVerifier;

it('verifies valid etsy signature', function () {
    $config = ['webhook_secret' => 'secret123'];
    $verifier = new EtsyWebhookVerifier($config);

    $payload = '{"foo":"bar"}';
    $signature = hash_hmac('sha256', $payload, 'secret123'); 
    
    

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
