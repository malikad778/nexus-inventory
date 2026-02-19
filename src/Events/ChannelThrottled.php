<?php

namespace Adnan\LaravelNexus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ChannelThrottled implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $channel,
        public int $retryAfter
    ) {}

    public function broadcastOn(): array
    {
        return ['nexus-alerts'];
    }

    public function broadcastAs(): string
    {
        return 'channel.throttled';
    }
}
