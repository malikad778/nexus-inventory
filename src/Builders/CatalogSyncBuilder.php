<?php

namespace Adnan\LaravelNexus\Builders;

use Adnan\LaravelNexus\Jobs\ChannelSyncBatchJob;
use Illuminate\Support\Facades\Bus;

class CatalogSyncBuilder
{
    protected array $channels = [];

    protected ?string $queue = null;

    protected string $connection = 'redis';

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
        $channels = ! empty($this->channels)
            ? $this->channels
            : array_keys(config('nexus.drivers', []));

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
