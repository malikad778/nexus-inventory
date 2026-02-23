<?php

namespace Malikad778\LaravelNexus\Facades;

use Illuminate\Support\Facades\Facade;
use Malikad778\LaravelNexus\Builders\BatchBuilder;
use Malikad778\LaravelNexus\Builders\CatalogSyncBuilder;
use Malikad778\LaravelNexus\Builders\ProductSyncBuilder;
use Malikad778\LaravelNexus\Contracts\InventoryDriver;
use Malikad778\LaravelNexus\InventoryManager;

/**
 * @method static InventoryDriver driver(?string $driver = null)
 * @method static InventoryDriver channel(?string $name = null)
 * @method static InventoryManager context(array $context)
 * @method static CatalogSyncBuilder catalog(mixed $products = null)
 * @method static ProductSyncBuilder product(mixed $product)
 * @method static BatchBuilder batch(?string $name = null)
 *
 * @see InventoryManager
 */
class Nexus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'nexus';
    }
}
