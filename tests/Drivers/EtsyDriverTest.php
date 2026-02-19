<?php

use Adnan\LaravelNexus\DataTransferObjects\NexusProduct;
use Adnan\LaravelNexus\Facades\Nexus;
use Illuminate\Support\Facades\Http;

it('can fetch products from etsy', function () {
    config()->set('nexus.drivers.etsy', [
        'client_id' => 'key_123',
        'access_token' => 'at_123',
        'shop_id' => 'shop_123',
    ]);

    Http::fake([
        'api.etsy.com/v3/application/shops/shop_123/listings*' => Http::response([
            'results' => [
                [
                    'listing_id' => 111,
                    'title' => 'Etsy Item',
                    'skus' => ['ETSY-SKU'],
                    'price' => ['amount' => 2000, 'divisor' => 100],
                    'quantity' => 5,
                ],
            ],
        ], 200),
    ]);

    $driver = Nexus::driver('etsy');
    $products = $driver->getProducts(now()->subDay());

    expect($products)->toHaveCount(1);
    expect($products->first())->toBeInstanceOf(NexusProduct::class);
    expect($products->first()->sku)->toBe('ETSY-SKU');
    expect($products->first()->name)->toBe('Etsy Item');
    expect($products->first()->price)->toBe(20.00);
    expect($products->first()->quantity)->toBe(5);
    expect($products->first()->id)->toBe('111');
});

it('can update inventory on etsy', function () {
    config()->set('nexus.drivers.etsy', [
        'client_id' => 'key_123',
        'refresh_token' => 'rt_123', // Force refresh flow
        'shop_id' => 'shop_123',
    ]);

    Http::fake([
        'api.etsy.com/v3/public/oauth/token' => Http::response([
            'access_token' => 'new_at_123',
        ], 200),
        'api.etsy.com/v3/application/listings/111/inventory' => Http::response([
            'products' => [
                [
                    'product_id' => 999,
                    'offerings' => [
                        ['offering_id' => 888, 'quantity' => 5],
                    ],
                ],
            ],
        ], 200),
        'api.etsy.com/v3/application/shops/shop_123/listings/111/inventory' => Http::response([
            'products' => [],
        ], 200),
    ]);

    $driver = Nexus::driver('etsy');
    $result = $driver->updateInventory('111', 10);

    expect($result)->toBeTrue();

    // Verify Refresh Token Call
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.etsy.com/v3/public/oauth/token' &&
               $request['grant_type'] === 'refresh_token' &&
               $request['refresh_token'] === 'rt_123';
    });

    // Verify Inventory Update
    Http::assertSent(function ($request) {
        if ($request->url() !== 'https://api.etsy.com/v3/application/shops/shop_123/listings/111/inventory') {
            return false;
        }

        return $request->method() === 'PUT' &&
               $request->hasHeader('x-api-key') &&
               $request->header('Authorization')[0] === 'Bearer new_at_123' &&
               $request['products'][0]['offerings'][0]['quantity'] === 10;
    });
});
