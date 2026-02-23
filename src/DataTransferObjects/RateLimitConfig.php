<?php

namespace Malikad778\LaravelNexus\DataTransferObjects;

class RateLimitConfig
{
    public function __construct(
        public int $capacity,
        public int $rate, 
        public int $cost = 1
    ) {}
}
