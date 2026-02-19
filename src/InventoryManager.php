<?php

namespace Adnan\LaravelNexus;

use Illuminate\Support\Manager;
use Adnan\LaravelNexus\Contracts\InventoryDriver;
use Adnan\LaravelNexus\Drivers\Shopify\ShopifyDriver;

class InventoryManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('nexus.default', 'shopify');
    }

    public function createShopifyDriver(): InventoryDriver
    {
        return new ShopifyDriver(
            config('nexus.drivers.shopify')
        );
    }

    public function createWooCommerceDriver(): InventoryDriver
    {
        return new \Adnan\LaravelNexus\Drivers\WooCommerce\WooCommerceDriver(
            config('nexus.drivers.woocommerce')
        );
    }

    public function createAmazonDriver(): InventoryDriver
    {
        return new \Adnan\LaravelNexus\Drivers\Amazon\AmazonDriver(
            config('nexus.drivers.amazon')
        );
    }

    public function createEtsyDriver(): InventoryDriver
    {
        return new \Adnan\LaravelNexus\Drivers\Etsy\EtsyDriver(
            config('nexus.drivers.etsy')
        );
    }
}
