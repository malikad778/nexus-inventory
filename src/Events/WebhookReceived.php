<?php

namespace Malikad778\LaravelNexus\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $channel,
        public array $payload,
        public array $headers,
        public int $logId
    ) {}
}
