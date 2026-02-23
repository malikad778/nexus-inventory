<?php

namespace Malikad778\LaravelNexus\Tests\Unit\Webhooks;

use Illuminate\Http\Request;
use Malikad778\LaravelNexus\Webhooks\Verifiers\WooCommerceWebhookVerifier;

it('verifies valid woocommerce signature', function () {
    $config = ['webhook_secret' => 'secret123'];
    $verifier = new WooCommerceWebhookVerifier($config);

    $payload = '{"foo":"bar"}';
    $signature = base64_encode(hash_hmac('sha256', $payload, 'secret123', true));

    $request = Request::create('/msg', 'POST', [], [], [], [], $payload);
    $request->headers->set('x-wc-webhook-signature', $signature);

    expect($verifier->verify($request))->toBeTrue();
});

it('rejects invalid woocommerce signature', function () {
    $config = ['webhook_secret' => 'secret123'];
    $verifier = new WooCommerceWebhookVerifier($config);

    $payload = '{"foo":"bar"}';

    $request = Request::create('/msg', 'POST', [], [], [], [], $payload);
    $request->headers->set('x-wc-webhook-signature', 'invalid');

    expect($verifier->verify($request))->toBeFalse();
});
