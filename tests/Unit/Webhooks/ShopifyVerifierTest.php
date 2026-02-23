<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Malikad778\LaravelNexus\Webhooks\Verifiers\ShopifyWebhookVerifier;

it('verifies valid shopify signature', function () {
    Config::set('nexus.drivers.shopify.client_secret', 'secret123');

    $payload = '{"foo":"bar"}';
    $signature = base64_encode(hash_hmac('sha256', $payload, 'secret123', true));

    $request = Request::create('/', 'POST', [], [], [], [], $payload);
    $request->headers->set('X-Shopify-Hmac-Sha256', $signature);

    $verifier = new ShopifyWebhookVerifier;
    expect($verifier->verify($request))->toBeTrue();
});

it('rejects invalid shopify signature', function () {
    Config::set('nexus.drivers.shopify.client_secret', 'secret123');

    $payload = '{"foo":"bar"}';
    $signature = 'invalid_signature';

    $request = Request::create('/', 'POST', [], [], [], [], $payload);
    $request->headers->set('X-Shopify-Hmac-Sha256', $signature);

    $verifier = new ShopifyWebhookVerifier;
    expect($verifier->verify($request))->toBeFalse();
});
