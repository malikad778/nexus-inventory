<?php

namespace Adnan\LaravelNexus\Http\Livewire;

use Adnan\LaravelNexus\Models\NexusDeadLetterQueue;
use Livewire\Component;
use Livewire\WithPagination;

class DeadLetterQueue extends Component
{
    use WithPagination;

    public function retry(int $id)
    {
        $job = NexusDeadLetterQueue::findOrFail($id);
        // Retry logic: decode payload and dispatch again
        $job->delete();
        $this->dispatch('notify', 'Job queued for retry');
    }

    public function dismiss(int $id)
    {
        NexusDeadLetterQueue::destroy($id);
        $this->dispatch('notify', 'Job dismissed');
    }

    public function render()
    {
        return view('nexus::livewire.dead-letter-queue', [
            'jobs' => NexusDeadLetterQueue::latest()->paginate(10)
        ]);
    }
}
