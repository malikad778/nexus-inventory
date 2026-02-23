<?php

namespace Malikad778\LaravelNexus\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;

class NexusVariant implements Arrayable
{
    public function __construct(
        public string $id,
        public string $sku,
        public ?float $price,
        public ?int $quantity,
        public array $options = [],
        public array $remoteData = []
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'options' => $this->options,
            'remote_data' => $this->remoteData,
        ];
    }
}
