<?php

use Illuminate\Support\Facades\Http;
use Adnan\LaravelNexus\Facades\Nexus;
use Adnan\LaravelNexus\DataTransferObjects\NexusProduct;

it('can fetch products from woocommerce', function () {
    config()->set('nexus.drivers.woocommerce', [
        'store_url' => 'https://test-store.com',
        'consumer_key' => 'ck_test',
        'consumer_secret' => 'cs_test',
    ]);

    Http::fake([
        'test-store.com/wp-json/wc/v3/products*' => Http::response([
            [
                'id' => 101,
                'name' => 'Woo Album',
                'sku' => 'WOO-ALBUM',
                'price' => '15.00',
                'stock_quantity' => 20,
            ]
        ], 200),
    ]);

    $driver = Nexus::driver('woocommerce');
    $products = $driver->getProducts(now()->subDay());

    expect($products)->toHaveCount(1);
    expect($products->first())->toBeInstanceOf(NexusProduct::class);
    expect($products->first()->sku)->toBe('WOO-ALBUM');
    expect($products->first()->name)->toBe('Woo Album');
    expect($products->first()->price)->toBe(15.00);
    expect($products->first()->quantity)->toBe(20);
    expect($products->first()->remote_id)->toBe('101');
});

it('can update inventory on woocommerce', function () {
    config()->set('nexus.drivers.woocommerce', [
        'store_url' => 'https://test-store.com',
        'consumer_key' => 'ck_test',
        'consumer_secret' => 'cs_test',
    ]);

    Http::fake([
        'test-store.com/wp-json/wc/v3/products/101' => Http::response([
            'id' => 101,
            'stock_quantity' => 50,
        ], 200),
    ]);

    $driver = Nexus::driver('woocommerce');
    $result = $driver->updateInventory('101', 50);

    expect($result)->toBeTrue();

    // Verify the PUT call was made with correct data
    Http::assertSent(function ($request) {
        return $request->url() === 'https://test-store.com/wp-json/wc/v3/products/101' &&
               $request->method() === 'PUT' &&
               $request['manage_stock'] === true &&
               $request['stock_quantity'] === 50;
    });
});
