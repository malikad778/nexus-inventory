<?php

namespace Malikad778\LaravelNexus\Http\Livewire;

use Illuminate\Support\Facades\Queue;
use Livewire\Component;
use Livewire\WithPagination;
use Malikad778\LaravelNexus\Models\NexusDeadLetterQueue;

class DeadLetterQueue extends Component
{
    use WithPagination;

    public function retry(int $id)
    {
        $record = NexusDeadLetterQueue::findOrFail($id);

        // The model casts `payload` as 'array', so Eloquent already decoded it.
        // We just re-encode it to push raw JSON back onto the queue.
        if (! empty($record->payload)) {
            Queue::pushRaw(json_encode($record->payload), 'nexus');
        }

        $record->delete();
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

    public function retryAll()
    {
        $failed = NexusDeadLetterQueue::where('status', 'failed')->get();

        $count = 0;
        foreach ($failed as $record) {
            // The model casts `payload` as 'array', so Eloquent already decoded it.
            if (! empty($record->payload)) {
                Queue::pushRaw(json_encode($record->payload), 'nexus');
                $record->delete();
                $count++;
            }
        }

        $this->dispatch('notify', "{$count} job(s) queued for retry");
    }

    public function render()
    {
        return view('nexus::livewire.dead-letter-queue', [
            'jobs' => NexusDeadLetterQueue::latest()->paginate(10),
        ]);
    }
}
