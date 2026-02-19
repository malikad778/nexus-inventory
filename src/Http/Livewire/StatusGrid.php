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
            $stats[$driver] = [
                'health' => 'Healthy', // Basic mockup of logic
                'last_sync' => NexusSyncJob::where('channel', $driver)->latest()->value('completed_at'),
                'pending' => NexusSyncJob::where('channel', $driver)->whereNull('completed_at')->count(),
            ];
        }

        return view('nexus::livewire.status-grid', [
            'stats' => $stats,
        ]);
    }
}
