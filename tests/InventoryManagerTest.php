<?php

use Adnan\LaravelNexus\Contracts\InventoryDriver;
use Adnan\LaravelNexus\Drivers\Shopify\ShopifyDriver;
use Adnan\LaravelNexus\InventoryManager;
use Adnan\LaravelNexus\Facades\Nexus;

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
