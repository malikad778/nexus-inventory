<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class DashboardTest extends \Malikad778\LaravelNexus\Tests\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        
        Route::nexusDashboard('nexus');
    }

    
    public function it_can_render_dashboard()
    {
        $response = $this->get('/nexus');
        $response->assertOk();

        if (class_exists(\Livewire\Livewire::class)) {
            $response->assertSeeLivewire('nexus-status-grid');
        } else {
            $response->assertSee('Dashboard');
        }
    }

    
    public function it_can_render_webhooks_page()
    {
        DB::table('nexus_webhook_logs')->insert([
            'channel' => 'shopify',
            'topic' => 'products/update',
            'payload' => '{}',
            'status' => 'processed',
            'created_at' => now(),
        ]);

        $response = $this->get('/nexus/webhooks');
        $response->assertOk();

        if (class_exists(\Livewire\Livewire::class)) {
            $response->assertSeeLivewire('nexus-webhook-log');

            \Livewire\Livewire::test(\Malikad778\LaravelNexus\Http\Livewire\WebhookLog::class)
                ->assertSee('shopify')
                ->assertSee('products/update');
        } else {
            $response->assertSee('Webhook Logs');
        }
    }

    
    public function it_can_render_dlq_page()
    {
        DB::table('nexus_dead_letter_queue')->insert([
            'job_class' => 'SomeJob',
            'payload' => '{}',
            'status' => 'failed',
            'exception' => 'Error',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get('/nexus/dlq');
        $response->assertOk();

        if (class_exists(\Livewire\Livewire::class)) {
            $response->assertSeeLivewire('nexus-dead-letter-queue');

            \Livewire\Livewire::test(\Malikad778\LaravelNexus\Http\Livewire\DeadLetterQueue::class)
                ->assertSee('SomeJob');
        } else {
            $response->assertSee('Dead Letter Queue');
        }
    }

    
    public function it_can_render_jobs_page_with_missing_table()
    {
        
        $response = $this->get('/nexus/jobs');
        $response->assertOk();
        $response->assertSee('Configuration Needed');
    }

    
    public function it_can_render_jobs_page_with_batches()
    {
        
        if (! \Illuminate\Support\Facades\Schema::hasTable('job_batches')) {
            \Illuminate\Support\Facades\Schema::create('job_batches', function ($table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->text('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        DB::table('job_batches')->insert([
            'id' => 'batch-123',
            'name' => 'Test Batch',
            'total_jobs' => 10,
            'pending_jobs' => 5,
            'failed_jobs' => 0,
            'failed_job_ids' => '[]',
            'created_at' => time(),
        ]);

        $response = $this->get('/nexus/jobs');
        $response->assertOk();
        $response->assertSee('Test Batch');
        $response->assertSee('50%'); 
    }

    
    public function it_can_dismiss_dlq_job()
    {
        $id = DB::table('nexus_dead_letter_queue')->insertGetId([
            'job_class' => 'SomeJob',
            'payload' => '{}',
            'status' => 'failed',
            'created_at' => now(),
        ]);

        $response = $this->delete("/nexus/dlq/{$id}");
        $response->assertRedirect();

        expect(DB::table('nexus_dead_letter_queue')->find($id))->toBeNull();
    }
}
