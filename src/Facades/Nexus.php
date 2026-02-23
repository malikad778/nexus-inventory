<?php

namespace Malikad778\LaravelNexus\Facades;

use Illuminate\Support\Facades\Facade;

class Nexus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'nexus';
    }
}
