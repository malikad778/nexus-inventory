<?php

namespace Adnan\LaravelNexus\Tests\Unit;

use Adnan\LaravelNexus\Facades\Nexus;
use Adnan\LaravelNexus\InventoryManager;
use Adnan\LaravelNexus\Drivers\Shopify\ShopifyDriver;



it('resolves default driver', function () {
    $driver = Nexus::driver();
    expect($driver)->toBeInstanceOf(ShopifyDriver::class);
});

it('resolves driver via channel alias', function () {
    $driver = Nexus::channel('shopify');
    expect($driver)->toBeInstanceOf(ShopifyDriver::class);
});

it('passes context to driver', function () {
    // We can't easily inspect the driver's config because it's protected.
    // However, we can use reflection or check if the driver works with a mocked config.
    // Or simpler: The ShopifyDriver Constructor takes config.
    // Let's rely on the fact that if we pass invalid config, it might fail or we can check property.
    
    $context = ['shop_url' => 'override.myshopify.com'];
    
    // Create a new manager instance via Facade's context method
    $manager = Nexus::context($context);
    
    expect($manager)->toBeInstanceOf(InventoryManager::class);
    expect($manager)->not->toBe(Nexus::getFacadeRoot()); // Should be a clone/new instance
    
    $driver = $manager->driver('shopify');
    
    // detailed check using reflection
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
