<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Malikad778\LaravelNexus\Events\AfterInventorySync;
use Malikad778\LaravelNexus\Events\BeforeInventorySync;
use Malikad778\LaravelNexus\Jobs\ChannelSyncBatchJob;
use Malikad778\LaravelNexus\Jobs\PushInventoryJob;
use Malikad778\LaravelNexus\Models\ChannelMapping;

uses(RefreshDatabase::class);

class TestProduct extends Model
{
    protected $table = 'test_products';

    protected $guarded = [];
}

beforeEach(function () {
    Schema::create('test_products', function ($table) {
        $table->id();
        $table->string('sku');
        $table->integer('quantity');
        $table->timestamps();
    });
});

it('dispatches events and pushes inventory for existing mappings', function () {
    Event::fake();
    Bus::fake();

    $product = TestProduct::create(['sku' => 'TEST-1', 'quantity' => 10]);

    ChannelMapping::create([
        'channel' => 'shopify',
        'remote_id' => '123456',
        'syncable_type' => TestProduct::class,
        'syncable_id' => $product->id,
    ]);

    $job = new ChannelSyncBatchJob('shopify');
    $job->handle();

    Event::assertDispatched(BeforeInventorySync::class, function ($event) {
        return $event->channel === 'shopify';
    });

    Bus::assertDispatched(PushInventoryJob::class, function ($job) {
        return $job->channel === 'shopify' && $job->remoteId === '123456' && $job->quantity === 10;
    });

    Event::assertDispatched(AfterInventorySync::class, function ($event) {
        return $event->channel === 'shopify' && $event->productsSynced === 1;
    });
});
