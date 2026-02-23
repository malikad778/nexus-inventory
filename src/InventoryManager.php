<?php

namespace Malikad778\LaravelNexus;

use Illuminate\Support\Manager;
use Malikad778\LaravelNexus\Contracts\InventoryDriver;
use Malikad778\LaravelNexus\Drivers\Amazon\AmazonDriver;
use Malikad778\LaravelNexus\Drivers\Etsy\EtsyDriver;
use Malikad778\LaravelNexus\Drivers\Shopify\ShopifyDriver;
use Malikad778\LaravelNexus\Drivers\WooCommerce\WooCommerceDriver;

class InventoryManager extends Manager
{
    protected array $context = [];

    public function context(array $context): self
    {
        $instance = clone $this;
        $instance->context = $context;
        
        $instance->forgetDrivers();

        return $instance;
    }

    public function channel(?string $name = null): InventoryDriver
    {
        return $this->driver($name);
    }

    public function catalog(mixed $products = null): Builders\CatalogSyncBuilder
    {
        return new Builders\CatalogSyncBuilder($products);
    }

    public function product(mixed $product): Builders\ProductSyncBuilder
    {
        return new Builders\ProductSyncBuilder($product);
    }

    public function batch(?string $name = null): Builders\BatchBuilder
    {
        $builder = new Builders\BatchBuilder;
        if ($name) {
            $builder->name($name);
        }

        return $builder;
    }

    public function getDefaultDriver()
    {
        return $this->config->get('nexus.default', 'shopify');
    }

    public function createShopifyDriver(): InventoryDriver
    {
        return new ShopifyDriver(
            array_merge(config('nexus.drivers.shopify', []), $this->context)
        );
    }

    public function createWooCommerceDriver(): InventoryDriver
    {
        return new WooCommerceDriver(
            array_merge(config('nexus.drivers.woocommerce', []), $this->context)
        );
    }

    public function createAmazonDriver(): InventoryDriver
    {
        return new AmazonDriver(
            array_merge(config('nexus.drivers.amazon', []), $this->context)
        );
    }

    public function createEtsyDriver(): InventoryDriver
    {
        return new EtsyDriver(
            array_merge(config('nexus.drivers.etsy', []), $this->context)
        );
    }
}
