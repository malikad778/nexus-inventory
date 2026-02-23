<?php

namespace Malikad778\LaravelNexus\Builders;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

class BatchBuilder
{
    protected string $name = 'nexus-batch';

    protected array $jobs = [];

    protected bool $allowFailures = true;

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function add(array|object $jobs): self
    {
        if (is_array($jobs)) {
            $this->jobs = array_merge($this->jobs, $jobs);
        } else {
            $this->jobs[] = $jobs;
        }

        return $this;
    }

    public function dispatch(): Batch
    {
        $batch = Bus::batch($this->jobs)
            ->name($this->name);

        if ($this->allowFailures) {
            $batch->allowFailures();
        }

        return $batch->dispatch();
    }
}
