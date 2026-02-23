<?php

use Illuminate\Support\Facades\Http;
use Malikad778\LaravelNexus\DataTransferObjects\NexusProduct;
use Malikad778\LaravelNexus\Facades\Nexus;

it('can fetch products from shopify', function () {
    config()->set('nexus.drivers.shopify', [
        'shop_url' => 'test-shop.myshopify.com',
        'access_token' => 'test-token',
        'api_version' => '2024-01',
    ]);

    Http::fake([
        'test-shop.myshopify.com/admin/api/2024-01/products.json*' => Http::response([
            'products' => [
                [
                    'id' => 123456,
                    'title' => 'Test Product',
                    'variants' => [
                        [
                            'id' => 987654,
                            'price' => '19.99',
                            'sku' => 'TEST-SKU',
                            'inventory_quantity' => 10,
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $driver = Nexus::driver('shopify');
    $products = $driver->getProducts(now()->subDay());

    expect($products)->toHaveCount(1);
    expect($products->first())->toBeInstanceOf(NexusProduct::class);
    expect($products->first()->sku)->toBe('TEST-SKU');
    expect($products->first()->name)->toBe('Test Product');
    expect($products->first()->price)->toBe(19.99);
    expect($products->first()->quantity)->toBe(10);
    expect($products->first()->id)->toBe('123456');
});

it('can update inventory on shopify', function () {
    config()->set('nexus.drivers.shopify', [
        'shop_url' => 'test-shop.myshopify.com',
        'access_token' => 'test-token',
        'api_version' => '2024-01',
        'location_id' => '888888',
    ]);

    
    Http::fake([
        'test-shop.myshopify.com/admin/api/2024-01/variants/987654.json' => Http::response([
            'variant' => [
                'id' => 987654,
                'inventory_item_id' => 777777,
            ],
        ], 200),
        'test-shop.myshopify.com/admin/api/2024-01/inventory_levels/set.json' => Http::response([
            'inventory_level' => [
                'inventory_item_id' => 777777,
                'location_id' => 888888,
                'available' => 50,
            ],
        ], 200),
    ]);

    $driver = Nexus::driver('shopify');
    $result = $driver->updateInventory('987654', 50);

    expect($result)->toBeTrue();

    
    Http::assertSent(function ($request) {
        return $request->url() === 'https://test-shop.myshopify.com/admin/api/2024-01/inventory_levels/set.json' &&
               $request['location_id'] === '888888' &&
               $request['inventory_item_id'] === 777777 &&
               $request['available'] === 50;
    });
});
