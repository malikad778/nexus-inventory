<?php

namespace Adnan\LaravelNexus\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
