<?php

namespace Adnan\LaravelNexus\Http\Livewire;

use Adnan\LaravelNexus\Models\NexusSyncJob;
use Livewire\Component;

class StatusGrid extends Component
{
    public function render()
    {
        $drivers = array_keys(config('nexus.drivers', []));
        $stats = [];

        foreach ($drivers as $driver) {
            /** @var NexusSyncJob|null $lastError */
            $lastError = NexusSyncJob::where('channel', $driver)->where('status', 'failed')->latest()->first();
            $lastThrottle = \Illuminate\Support\Facades\DB::table('nexus_rate_limit_logs')
                ->where('channel', $driver)
                ->where('was_limited', true)
                ->latest()
                ->first();

            $health = 'Connected';
            if ($lastThrottle && \Illuminate\Support\Carbon::parse($lastThrottle->created_at)->diffInMinutes() < 10) {
                $health = 'Throttled';
            } elseif ($lastError && \Illuminate\Support\Carbon::parse($lastError->finished_at)->diffInHours() < 1) {
                $health = 'Disconnected';
            }

            $rateLimit = \Illuminate\Support\Facades\DB::table('nexus_rate_limit_logs')
                ->where('channel', $driver)
                ->latest()
                ->first();

            $stats[$driver] = [
                'health' => $health,
                'last_sync' => NexusSyncJob::where('channel', $driver)->latest()->value('finished_at'),
                'pending' => NexusSyncJob::where('channel', $driver)->whereNull('finished_at')->count(),
                'tokens' => $rateLimit ? $rateLimit->tokens_remaining : null,
                'capacity' => config("nexus.rate_limits.{$driver}.capacity", 10),
            ];
        }

        return view('nexus::livewire.status-grid', [
            'stats' => $stats,
        ]);
    }
}
