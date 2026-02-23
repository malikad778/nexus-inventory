<?php

namespace Malikad778\LaravelNexus;

use Exception;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Malikad778\LaravelNexus\Commands\NexusCommand;
use Malikad778\LaravelNexus\Http\Controllers\DashboardController;
use Malikad778\LaravelNexus\Http\Controllers\WebhookController;
use Malikad778\LaravelNexus\Http\Livewire\DeadLetterQueue;
use Malikad778\LaravelNexus\Http\Livewire\StatusGrid;
use Malikad778\LaravelNexus\Http\Livewire\WebhookLog;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class NexusServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-nexus')
            ->hasConfigFile('nexus')
            ->hasViews()
            ->hasMigrations(['create_nexus_tables', 'create_nexus_dlq_table', 'create_nexus_webhook_table', 'create_audit_remediation_tables'])
            ->hasCommand(NexusCommand::class);
    }

    public function packageRegistered()
    {
        $this->app->singleton('nexus', function ($app) {
            return new InventoryManager($app);
        });
    }

    public function packageBooted()
    {
        
        if (class_exists(Livewire::class)) {
            Livewire::component('nexus-status-grid', StatusGrid::class);
            Livewire::component('nexus-webhook-log', WebhookLog::class);
            Livewire::component('nexus-dead-letter-queue', DeadLetterQueue::class);
        }

        $this->app['events']->listen(JobFailed::class, function ($event) {
            
            if (str_starts_with($event->job->resolveName(), 'Malikad778\LaravelNexus')) {
                try {
                    DB::table('nexus_dead_letter_queue')->insert([
                        'channel' => null, 
                        'job_class' => $event->job->resolveName(),
                        'payload' => json_encode($event->job->payload()),
                        'exception' => (string) $event->exception,
                        'status' => 'failed',
                        'attempts' => $event->job->attempts(),
                        'last_attempt_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (Exception $e) {
                    
                }
            }
        });

        
        $this->registerRoutes();
    }

    protected function registerRoutes()
    {
        Route::macro('nexusWebhooks', function (string $prefix = 'nexus/webhooks') {
            Route::post($prefix.'/{channel}', [WebhookController::class, 'handle'])
                ->name('nexus.webhooks');
        });

        Route::macro('nexusDashboard', function (string $prefix = 'nexus') {
            Route::group([
                'prefix' => $prefix,
                'middleware' => config('nexus.dashboard_middleware', ['web']),
            ], function () {
                Route::get('/', [DashboardController::class, 'index'])->name('nexus.dashboard');
                Route::get('/jobs', [DashboardController::class, 'jobs'])->name('nexus.dashboard.jobs');
                Route::get('/webhooks', [DashboardController::class, 'webhooks'])->name('nexus.dashboard.webhooks');
                Route::get('/dlq', [DashboardController::class, 'dlq'])->name('nexus.dashboard.dlq');

                
                Route::post('/dlq/{id}/retry', [DashboardController::class, 'retryJob'])->name('nexus.dashboard.dlq.retry');
                Route::delete('/dlq/{id}', [DashboardController::class, 'dismissJob'])->name('nexus.dashboard.dlq.dismiss');
            });
        });
    }
}
