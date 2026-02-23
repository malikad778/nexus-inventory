<?php

namespace Malikad778\LaravelNexus\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Malikad778\LaravelNexus\Models\ChannelMapping;

trait Syncable
{
    public function channelMappings(): MorphMany
    {
        return $this->morphMany(ChannelMapping::class, 'syncable');
    }

    public function getRemoteId(string $channel): ?string
    {
        return $this->channelMappings()
            ->where('channel', $channel)
            ->value('remote_id');
    }

    public function setRemoteId(string $channel, string $remoteId): void
    {
        $this->channelMappings()->updateOrCreate(
            ['channel' => $channel],
            ['remote_id' => $remoteId]
        );
    }
}
