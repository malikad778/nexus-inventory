<?php

namespace Malikad778\LaravelNexus\Tests\Unit;

use Malikad778\LaravelNexus\Drivers\Shopify\ShopifyDriver;
use Malikad778\LaravelNexus\Facades\Nexus;
use Malikad778\LaravelNexus\InventoryManager;

it('resolves default driver', function () {
    $driver = Nexus::driver();
    expect($driver)->toBeInstanceOf(ShopifyDriver::class);
});

it('resolves driver via channel alias', function () {
    $driver = Nexus::channel('shopify');
    expect($driver)->toBeInstanceOf(ShopifyDriver::class);
});

it('passes context to driver', function () {
    
    
    
    

    $context = ['shop_url' => 'override.myshopify.com'];

    
    $manager = Nexus::context($context);

    expect($manager)->toBeInstanceOf(InventoryManager::class);
    expect($manager)->not->toBe(Nexus::getFacadeRoot()); 

    $driver = $manager->driver('shopify');

    
    $reflection = new \ReflectionClass($driver);
    $property = $reflection->getProperty('config');
    $property->setAccessible(true);
    $config = $property->getValue($driver);

    expect($config['shop_url'])->toBe('override.myshopify.com');
});

it('does not leak context to singleton', function () {
    $context = ['shop_url' => 'leaked.myshopify.com'];
    Nexus::context($context)->driver('shopify');

    $driver = Nexus::driver('shopify');

    $reflection = new \ReflectionClass($driver);
    $property = $reflection->getProperty('config');
    $property->setAccessible(true);
    $config = $property->getValue($driver);

    expect($config['shop_url'])->not->toBe('leaked.myshopify.com');
});
