<?php

namespace Malikad778\LaravelNexus\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $channel
 * @property string $syncable_type
 * @property int $syncable_id
 * @property string|null $remote_id
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ChannelMapping extends Model
{
    protected $table = 'nexus_channel_mappings';

    protected $guarded = [];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function syncable(): MorphTo
    {
        return $this->morphTo();
    }
}
