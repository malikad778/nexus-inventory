<?php

namespace Adnan\LaravelNexus\RateLimiting;

use Illuminate\Support\Facades\Redis;

class TokenBucket
{
    /**
     * Attempt to acquire tokens from the bucket.
     *
     * @param string $key Identifier for the bucket (e.g., channel name)
     * @param int $capacity Max tokens in the bucket
     * @param float $rate Tokens added per second
     * @param int $cost Tokens required for this operation
     * @return bool
     */
    public function acquire(string $key, int $capacity, float $rate, int $cost = 1): bool
    {
        $script = <<<LUA
            local key = KEYS[1]
            local capacity = tonumber(ARGV[1])
            local rate = tonumber(ARGV[2])
            local cost = tonumber(ARGV[3])
            local now = tonumber(ARGV[4])

            local last_refill = tonumber(redis.call('HGET', key, 'last_refill') or now)
            local tokens = tonumber(redis.call('HGET', key, 'tokens') or capacity)

            -- Refill tokens
            local delta = math.max(0, now - last_refill)
            local tokens_to_add = delta * rate
            tokens = math.min(capacity, tokens + tokens_to_add)

            if tokens >= cost then
                tokens = tokens - cost
                -- Update state with new timestamp only if we consumed tokens? 
                -- Actually, we should update timestamp regardless if we refilled, 
                -- effectively moving the "last refill" window forward.
                -- But simple implementation: set last_refill to now.
                
                redis.call('HSET', key, 'last_refill', now, 'tokens', tokens)
                redis.call('EXPIRE', key, 60 * 60) -- Expire in 1 hour if unused
                return 1
            else
                -- We verify pass, but don't consume/update if failed?
                -- Or standard leaky bucket updates the refill even on failure.
                -- Let's update the refill state so next check is accurate.
                
                redis.call('HSET', key, 'last_refill', now, 'tokens', tokens)
                redis.call('EXPIRE', key, 60 * 60)
                return 0
            end
LUA;

        $result = Redis::eval($script, 1, "nexus:limiter:{$key}", $capacity, $rate, $cost, microtime(true));

        return (bool) $result;
    }
}
