<?php

namespace Adnan\LaravelNexus;

use Adnan\LaravelNexus\Contracts\InventoryDriver;
use Adnan\LaravelNexus\Drivers\Shopify\ShopifyDriver;
use Illuminate\Support\Manager;

class InventoryManager extends Manager
{
    protected array $context = [];

    public function context(array $context): self
    {
        $instance = clone $this;
        $instance->context = $context;
        // Clear resolved drivers in the clone so we create fresh ones with new context
        $instance->forgetDrivers();

        return $instance;
    }

    public function channel(?string $name = null): InventoryDriver
    {
        return $this->driver($name);
    }

    public function catalog(): Builders\CatalogSyncBuilder
    {
        return new Builders\CatalogSyncBuilder;
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
        return new \Adnan\LaravelNexus\Drivers\WooCommerce\WooCommerceDriver(
            array_merge(config('nexus.drivers.woocommerce', []), $this->context)
        );
    }

    public function createAmazonDriver(): InventoryDriver
    {
        return new \Adnan\LaravelNexus\Drivers\Amazon\AmazonDriver(
            array_merge(config('nexus.drivers.amazon', []), $this->context)
        );
    }

    public function createEtsyDriver(): InventoryDriver
    {
        return new \Adnan\LaravelNexus\Drivers\Etsy\EtsyDriver(
            array_merge(config('nexus.drivers.etsy', []), $this->context)
        );
    }
}
