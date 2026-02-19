<?php

namespace Adnan\LaravelNexus\Builders;

use Adnan\LaravelNexus\DataTransferObjects\NexusInventoryUpdate;
use Adnan\LaravelNexus\Facades\Nexus;
use Adnan\LaravelNexus\Jobs\PushInventoryJob;
use Illuminate\Database\Eloquent\Model;

class ProductSyncBuilder
{
    protected mixed $product;
    protected ?int $quantity = null;
    protected array $channels = [];

    public function __construct(mixed $product)
    {
        $this->product = $product;
    }

    public function updateInventory(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function sync(array $channels = []): array
    {
        $this->channels = $channels ?: array_keys(config('nexus.drivers', []));
        $results = [];

        foreach ($this->channels as $channel) {
            $remoteId = $this->resolveRemoteId($channel);
            
            if (!$remoteId) {
                $results[$channel] = false;
                continue;
            }

            $update = new NexusInventoryUpdate(
                sku: $this->resolveSku(),
                quantity: $this->quantity ?? 0,
                remoteId: $remoteId
            );

            // If quantity is set, we use PushInventoryJob which handles rate limiting
            // In a sync() call from the builder, we might want to dispatch it to the queue
            // but the spec example `Nexus::product($product)->updateInventory(42)->sync(['shopify'])`
            // implies an immediate or at least orchestrated action.
            
            $results[$channel] = PushInventoryJob::dispatch($channel, $update);
        }

        return $results;
    }

    protected function resolveRemoteId(string $channel): ?string
    {
        if ($this->product instanceof Model && method_exists($this->product, 'channelMappings')) {
            return $this->product->channelMappings()
                ->where('channel', $channel)
                ->value('remote_id');
        }

        if (is_array($this->product)) {
            return $this->product['remote_id'] ?? null;
        }

        return null;
    }

    protected function resolveSku(): string
    {
        if ($this->product instanceof Model) {
            return $this->product->sku ?? (string) $this->product->id;
        }

        if (is_array($this->product)) {
            return $this->product['sku'] ?? 'unknown';
        }

        return 'unknown';
    }
}
