<?php

namespace Adnan\LaravelNexus\DataTransferObjects;

class NexusProduct
{
    public function __construct(
        public string $sku,
        public string $name,
        public float $price,
        public int $quantity,
        public string $remote_id,
        public array $remote_data = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            sku: $data['sku'],
            name: $data['name'],
            price: (float) $data['price'],
            quantity: (int) $data['quantity'],
            remote_id: (string) $data['remote_id'],
            remote_data: $data['remote_data'] ?? [],
        );
    }
}
