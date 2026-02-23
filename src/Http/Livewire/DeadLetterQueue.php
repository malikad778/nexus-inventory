<?php

namespace Malikad778\LaravelNexus\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Malikad778\LaravelNexus\Models\NexusDeadLetterQueue;

class DeadLetterQueue extends Component
{
    use WithPagination;

    public function retry(int $id)
    {
        $job = NexusDeadLetterQueue::findOrFail($id);
        
        $job->delete();
        $this->dispatch('notify', 'Job queued for retry');
    }

    public function resolve(int $id)
    {
        $job = NexusDeadLetterQueue::findOrFail($id);
        $job->update(['status' => 'resolved']);
        $this->dispatch('notify', 'Job marked as resolved');
    }

    public function dismiss(int $id)
    {
        NexusDeadLetterQueue::destroy($id);
        $this->dispatch('notify', 'Job dismissed');
    }

    public function render()
    {
        return view('nexus::livewire.dead-letter-queue', [
            'jobs' => NexusDeadLetterQueue::latest()->paginate(10),
        ]);
    }
}
