<?php

namespace Malikad778\LaravelNexus\RateLimiting;

use Illuminate\Support\Facades\Redis;

class TokenBucket
{
    public function acquire(string $key, int $capacity, float $rate, int $cost = 1): bool
    {
        $script = <<<'LUA'
            local key = KEYS[1]
            local capacity = tonumber(ARGV[1])
            local rate = tonumber(ARGV[2])
            local cost = tonumber(ARGV[3])
            local now = tonumber(ARGV[4])

            local last_refill = tonumber(redis.call('HGET', key, 'last_refill') or now)
            local tokens = tonumber(redis.call('HGET', key, 'tokens') or capacity)

            local delta = math.max(0, now - last_refill)
            local tokens_to_add = delta * rate
            tokens = math.min(capacity, tokens + tokens_to_add)

            if tokens >= cost then
                tokens = tokens - cost
                redis.call('HSET', key, 'last_refill', now, 'tokens', tokens)
                redis.call('EXPIRE', key, 3600)
                return 1
            else
                redis.call('HSET', key, 'last_refill', now, 'tokens', tokens)
                redis.call('EXPIRE', key, 3600)
                return 0
            end
LUA;

        /** @var int $result */
        $result = Redis::eval(
            $script,
            ["nexus:limiter:{$key}", (string) $capacity, (string) $rate, (string) $cost, (string) microtime(true)],
            1
        );

        return (bool) $result;
    }
}
