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
        $variants = collect($data['variations'] ?? [])->map(fn ($v) => new NexusVariant(
            id: (string) ($v['id'] ?? $v), // Could be just ID
            sku: $v['sku'] ?? '',
            price: (float) ($v['price'] ?? 0),
            quantity: (int) ($v['stock_quantity'] ?? 0),
            options: array_values($v['attributes'] ?? []),
            remoteData: $v
        ));

        return new self(
            id: (string) $data['id'],
            name: $data['name'],
            sku: $data['sku'] ?? '',
            price: (float) ($data['price'] ?? 0),
            quantity: (int) ($data['stock_quantity'] ?? 0),
            variants: $variants,
            remoteData: $data,
            barcode: $data['barcode'] ?? null
        );
    }

    public static function fromAmazon(array $data): self
    {
        // Amazon SP-API items can have variations in relationships
        $variants = collect($data['relationships'] ?? [])
            ->filter(fn ($r) => $r['type'] === 'VARIATION')
            ->flatMap(fn ($r) => $r['childAsins'] ?? [])
            ->map(fn ($asin) => new NexusVariant(
                id: (string) $asin,
                sku: '', // ASINs don't always expose SKU in relationships
                price: null,
                quantity: null,
                options: [],
                remoteData: ['asin' => $asin]
            ));

        return new self(
            id: (string) ($data['asin'] ?? ''),
            name: $data['summaries'][0]['itemName'] ?? $data['item_name'] ?? 'Unknown',
            sku: $data['seller_sku'] ?? '',
            price: null,
            quantity: (int) ($data['quantity'] ?? 0),
            variants: $variants,
            remoteData: $data
        );
    }

    public static function fromEtsy(array $data): self
    {
        // Etsy variations are in offerings
        $variants = collect($data['products'] ?? [])->map(fn ($p) => new NexusVariant(
            id: (string) ($p['product_id'] ?? ''),
            sku: $p['sku'] ?? '',
            price: isset($p['offerings'][0]['price']) ? (float) ($p['offerings'][0]['price']['amount'] / $p['offerings'][0]['price']['divisor']) : 0.0,
            quantity: (int) ($p['offerings'][0]['quantity'] ?? 0),
            options: array_map(fn ($pv) => $pv['value'], $p['property_values'] ?? []),
            remoteData: $p
        ));

        return new self(
            id: (string) $data['listing_id'],
            name: $data['title'],
            sku: $data['skus'][0] ?? '',
            price: (float) (($data['price']['amount'] ?? 0) / ($data['price']['divisor'] ?? 100)),
            quantity: (int) ($data['quantity'] ?? 0),
            variants: $variants,
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
