<?php

use Illuminate\Support\Facades\Http;
use Malikad778\LaravelNexus\Facades\Nexus;

it('can update inventory on amazon', function () {
    config()->set('nexus.drivers.amazon', [
        'refresh_token' => 'rt_123',
        'client_id' => 'cid_123',
        'client_secret' => 'cs_123',
        'access_key_id' => 'ak_123',
        'secret_access_key' => 'sk_123',
        'region' => 'us-east-1',
        'seller_id' => 'sid_123',
    ]);

    Http::fake([
        'api.amazon.com/auth/o2/token' => Http::response([
            'access_token' => 'at_123',
            'expires_in' => 3600,
        ], 200),
        'sellingpartnerapi-na.amazon.com/listings/2021-08-01/items/sid_123/FAKE-SKU' => Http::response([
            'status' => 'ACCEPTED',
        ], 200),
    ]);

    $driver = Nexus::driver('amazon');
    $result = $driver->updateInventory('FAKE-SKU', 15);

    expect($result)->toBeTrue();

    
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.amazon.com/auth/o2/token' &&
               $request['grant_type'] === 'refresh_token' &&
               $request['refresh_token'] === 'rt_123';
    });

    
    Http::assertSent(function ($request) {
        $isApiCall = $request->url() === 'https://sellingpartnerapi-na.amazon.com/listings/2021-08-01/items/sid_123/FAKE-SKU';
        $hasMethod = $request->method() === 'PATCH';
        if (! $request->hasHeader('Authorization')) {
            return false;
        }

        $hasTokenHeader = $request->hasHeader('x-amz-access-token');
        $authHeaderContainsAws4 = str_contains($request->header('Authorization')[0], 'AWS4-HMAC-SHA256');

        return $isApiCall && $hasMethod && $hasTokenHeader && $authHeaderContainsAws4;
    });
});
