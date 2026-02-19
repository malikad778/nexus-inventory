<?php

namespace Adnan\LaravelNexus\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Adnan\LaravelNexus\Contracts\InventoryDriver driver(string $driver = null)
 * @see \Adnan\LaravelNexus\Nexus
 */
class Nexus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'nexus';
    }
}
