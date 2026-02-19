<?php

namespace Adnan\LaravelNexus\DataTransferObjects;

class RateLimitConfig
{
    public function __construct(
        public int $capacity,
        public int $rate, // Tokens per second
        public int $cost = 1
    ) {}
}
