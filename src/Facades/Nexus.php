<?php

namespace Adnan\LaravelNexus\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Adnan\LaravelNexus\Nexus
 */
class Nexus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'nexus';
    }
}
