<?php

namespace Malikad778\LaravelNexus\Builders;

use Illuminate\Support\Facades\Bus;
use Malikad778\LaravelNexus\Jobs\ChannelSyncBatchJob;

class CatalogSyncBuilder
{
    protected array $channels = [];

    protected ?string $queue = null;

    protected string $connection = 'redis';

    protected mixed $products = null;

    public function __construct(mixed $products = null)
    {
        $this->products = $products;
    }

    public function channels(array $channels): self
    {
        $this->channels = $channels;

        return $this;
    }

    public function onQueue(string $queue): self
    {
        $this->queue = $queue;

        return $this;
    }

    public function onConnection(string $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    public function sync(): \Illuminate\Bus\Batch
    {
        return $this->syncAll($this->channels);
    }

    public function syncAll(array $channels = []): \Illuminate\Bus\Batch
    {
        $channels = ! empty($channels) ? $channels : (! empty($this->channels) ? $this->channels : array_keys(config('nexus.drivers', [])));

        $jobs = [];
        foreach ($channels as $channel) {
            $job = new ChannelSyncBatchJob($channel);
            if ($this->queue) {
                $job->onQueue($this->queue);
            }
            if ($this->connection) {
                $job->onConnection($this->connection);
            }
            $jobs[] = $job;
        }

        return Bus::batch($jobs)
            ->name('nexus-catalog-sync')
            ->allowFailures()
            ->dispatch();
    }
}
