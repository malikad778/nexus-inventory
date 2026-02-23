<?php

use Malikad778\LaravelNexus\Contracts\InventoryDriver;
use Malikad778\LaravelNexus\Drivers\Shopify\ShopifyDriver;
use Malikad778\LaravelNexus\Facades\Nexus;

it('can resolve the default driver', function () {
    config()->set('nexus.drivers.shopify', [
        'shop_url' => 'test-shop',
        'access_token' => 'test-token',
        'api_version' => '2024-01',
    ]);

    $driver = Nexus::driver();

    expect($driver)->toBeInstanceOf(ShopifyDriver::class);
    expect($driver)->toBeInstanceOf(InventoryDriver::class);
});

it('can get channel name from driver', function () {
    config()->set('nexus.drivers.shopify', [
        'shop_url' => 'test-shop',
        'access_token' => 'test-token',
        'api_version' => '2024-01',
    ]);

    $driver = Nexus::driver('shopify');

    expect($driver->getChannelName())->toBe('shopify');
});
