<?php

namespace Adnan\LaravelNexus\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Adnan\LaravelNexus\Contracts\InventoryDriver channel(string $name = null)
 * @method static \Adnan\LaravelNexus\InventoryManager context(array $context)
 * @method static \Adnan\LaravelNexus\Builders\CatalogSyncBuilder catalog(mixed $products = null)
 * @method static \Adnan\LaravelNexus\Builders\ProductSyncBuilder product(mixed $product)
 * @method static \Adnan\LaravelNexus\Builders\BatchBuilder batch(string $name = null)
 *
 * @see \Adnan\LaravelNexus\InventoryManager
 */
class Nexus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'nexus';
    }
}
