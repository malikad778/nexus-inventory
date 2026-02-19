<?php

namespace Adnan\LaravelNexus\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class NexusProduct implements Arrayable
{
    /**
     * @param  Collection<int, NexusVariant>  $variants
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $sku,
        public ?float $price,
        public ?int $quantity,
        public Collection $variants,
        public array $remoteData = [],
        public ?string $barcode = null
    ) {}

    public static function fromShopify(array $data): self
    {
        $variants = collect($data['variants'] ?? [])->map(fn ($v) => new NexusVariant(
            id: (string) $v['id'],
            sku: $v['sku'] ?? '',
            price: (float) $v['price'],
            quantity: (int) ($v['inventory_quantity'] ?? 0),
            options: array_filter([$v['option1'] ?? null, $v['option2'] ?? null, $v['option3'] ?? null]),
            remoteData: $v
        ));

        // Use first variant for main product data if simple product
        $mainVariant = $variants->first();

        return new self(
            id: (string) $data['id'],
            name: $data['title'],
            sku: $mainVariant?->sku ?? '',
            price: $mainVariant?->price,
            quantity: $mainVariant?->quantity,
            variants: $variants,
            remoteData: $data,
            barcode: $mainVariant?->remoteData['barcode'] ?? null
        );
    }

    public static function fromWooCommerce(array $data): self
    {
        // WooCommerce structure handling... placeholder for now purely to satisfy static factory requirement
        return new self(
            id: (string) $data['id'],
            name: $data['name'],
            sku: $data['sku'] ?? '',
            price: (float) ($data['price'] ?? 0),
            quantity: (int) ($data['stock_quantity'] ?? 0),
            variants: collect(), // TODO: Parse variations
            remoteData: $data
        );
    }

    public static function fromAmazon(array $data): self
    {
        return new self(
            id: $data['asin'],
            name: $data['item_name'] ?? 'Unknown',
            sku: $data['seller_sku'],
            price: null, // Often unavailable in basic reports
            quantity: (int) ($data['quantity'] ?? 0),
            variants: collect(),
            remoteData: $data
        );
    }

    public static function fromEtsy(array $data): self
    {
        return new self(
            id: (string) $data['listing_id'],
            name: $data['title'],
            sku: $data['skus'][0] ?? '', // Use first SKU if available
            price: (float) (($data['price']['amount'] ?? 0) / ($data['price']['divisor'] ?? 100)),
            quantity: (int) ($data['quantity'] ?? 0),
            variants: collect(),
            remoteData: $data
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'barcode' => $this->barcode,
            'variants' => $this->variants->toArray(),
            'remote_data' => $this->remoteData,
        ];
    }
}
