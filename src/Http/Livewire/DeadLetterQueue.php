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

        $payload = is_array($record->payload) ? $record->payload : json_decode($record->payload, true);

        if (! empty($payload)) {
            // Re-push the raw serialized job payload directly onto the nexus queue.
            // The payload stored by Laravel's JobFailed event is the full job JSON
            // that the queue worker understands, so we can push it verbatim.
            Queue::pushRaw(json_encode($payload), 'nexus');
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
            $payload = is_array($record->payload) ? $record->payload : json_decode($record->payload, true);

            if (! empty($payload)) {
                Queue::pushRaw(json_encode($payload), 'nexus');
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
