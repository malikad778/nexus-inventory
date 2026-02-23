<?php

namespace Malikad778\LaravelNexus\Builders;

use Illuminate\Database\Eloquent\Model;
use Malikad778\LaravelNexus\DataTransferObjects\NexusInventoryUpdate;
use Malikad778\LaravelNexus\Facades\Nexus;
use Malikad778\LaravelNexus\Jobs\PushInventoryJob;

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

            if (! $remoteId) {
                $results[$channel] = false;

                continue;
            }

            $update = new NexusInventoryUpdate(
                sku: $this->resolveSku(),
                quantity: $this->quantity ?? 0,
                remoteId: $remoteId
            );

            
            
            
            
            $results[$channel] = PushInventoryJob::dispatch($channel, $update->remoteId, (int) $update->quantity);
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
            return $this->product->sku ?? (string) $this->product->getKey();
        }

        if (is_array($this->product)) {
            return $this->product['sku'] ?? 'unknown';
        }

        return 'unknown';
    }
}
