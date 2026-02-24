<?php

namespace Malikad778\LaravelNexus\Http\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class WebhookLog extends Component
{
    use WithPagination;

    public $search = '';

    public function render()
    {
        $query = DB::table('nexus_webhook_logs')->latest();

        if ($this->search) {
            $query->where('channel', 'like', "%{$this->search}%")
                ->orWhere('event', 'like', "%{$this->search}%");
        }

        return view('nexus::livewire.webhook-log', [
            'webhooks' => $query->paginate(10),
        ]);
    }
}
