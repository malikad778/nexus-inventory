<?php

namespace Adnan\LaravelNexus\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Adnan\LaravelNexus\Models\ChannelMapping;

class DashboardController extends Controller
{
    public function index()
    {
        // Gather Stats
        $stats = [
            'total_products' => \Illuminate\Support\Facades\Schema::hasTable('nexus_channel_mappings') 
                ? DB::table('nexus_channel_mappings')->count() 
                : 0,
            'failed_jobs' => \Illuminate\Support\Facades\Schema::hasTable('nexus_dead_letter_queue')
                ? DB::table('nexus_dead_letter_queue')->where('status', 'failed')->count()
                : 0,
            'webhook_logs' => \Illuminate\Support\Facades\Schema::hasTable('nexus_webhook_logs')
                ? DB::table('nexus_webhook_logs')->count()
                : 0,
            'channels' => [
                'shopify' => $this->getChannelStatus('shopify'),
                'woocommerce' => $this->getChannelStatus('woocommerce'),
                'amazon' => $this->getChannelStatus('amazon'),
                'etsy' => $this->getChannelStatus('etsy'),
            ]
        ];

        return view('nexus::dashboard', compact('stats'));
    }

    public function webhooks()
    {
        $logs = DB::table('nexus_webhook_logs')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('nexus::webhooks', compact('logs'));
    }

    public function jobs()
    {
        // Check if job_batches table exists
        if (! \Illuminate\Support\Facades\Schema::hasTable('job_batches')) {
            $batches = collect([]); 
            $error = "The 'job_batches' table does not exist. Please run 'php artisan queue:batches-table' and migrate.";
        } else {
            $batches = DB::table('job_batches')
                ->orderByDesc('created_at')
                ->paginate(20);
            $error = null;
        }

        return view('nexus::jobs', compact('batches', 'error'));
    }

    public function dlq()
    {
        $jobs = DB::table('nexus_dead_letter_queue')
            ->orderByDesc('last_attempt_at')
            ->paginate(20);

        return view('nexus::dlq', compact('jobs'));
    }

    public function retryJob($id)
    {
        $job = DB::table('nexus_dead_letter_queue')->find($id);

        if ($job && $job->status === 'failed') {
            // Dispatch the job again
            // We need to reconstruct the job object from payload?
            // Or typically, we just push the class and payload back to queue.
            
            // For simplicity in this phase, we'll use the job class and payload.
            // But `dispatch` usually expects a Job instance.
            // If the payload is the serialized job (standard Laravel), we can unserialize it.
            
            try {
                $payload = json_decode($job->payload, true);
                
                // If it's a standard Laravel job payload, it has 'job', 'data', etc.
                // We might need to use `Queue::pushRaw` or similar if we want to preserve exactness,
                // but usually we want to "Retry" which means creating a NEW job instance with same data.
                
                // Let's assume the payload is what `job->payload()` returned.
                // If it was serialized, we can `unserialize($payload['data']['command'])`.
                
                if (isset($payload['data']['command'])) {
                    $command = unserialize($payload['data']['command']);
                    dispatch($command);
                    
                    DB::table('nexus_dead_letter_queue')
                        ->where('id', $id)
                        ->update(['status' => 'retried', 'updated_at' => now()]);
                        
                    return back()->with('success', 'Job retried successfully.');
                }
            } catch (\Exception $e) {
                return back()->with('error', 'Failed to retry job: ' . $e->getMessage());
            }
        }

        return back()->with('error', 'Job not found or not failed.');
    }

    public function dismissJob($id)
    {
        DB::table('nexus_dead_letter_queue')
            ->where('id', $id)
            ->delete();

        return back()->with('success', 'Job dismissed successfully.');
    }

    protected function getChannelStatus(string $channel)
    {
        // Logic to determine status (active/inactive/errors)
        // For now, check if config exists and if there are recent errors
        $configured = ! empty(config("nexus.drivers.{$channel}"));
        
        $errors = \Illuminate\Support\Facades\Schema::hasTable('nexus_dead_letter_queue')
            ? DB::table('nexus_dead_letter_queue')
                ->where('channel', $channel)
                ->where('status', 'failed')
                ->where('created_at', '>=', now()->subHours(24))
                ->count()
            : 0;

        return [
            'name' => ucfirst($channel),
            'configured' => $configured,
            'status' => $configured ? ($errors > 0 ? 'warning' : 'healthy') : 'inactive',
            'errors_24h' => $errors,
        ];
    }
}
