<?php

namespace Adnan\LaravelNexus;

use Adnan\LaravelNexus\Commands\NexusCommand;
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

        // Register Livewire Components
        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::component('nexus-status-grid', Http\Livewire\StatusGrid::class);
            \Livewire\Livewire::component('nexus-webhook-log', Http\Livewire\WebhookLog::class);
            \Livewire\Livewire::component('nexus-dead-letter-queue', Http\Livewire\DeadLetterQueue::class);
        }
    }

    public function packageBooted()
    {
        $this->app['events']->listen(\Illuminate\Queue\Events\JobFailed::class, function ($event) {
            // Check if it's a Nexus job (namespace check)
            if (str_starts_with($event->job->resolveName(), 'Adnan\LaravelNexus')) {
                try {
                    \Illuminate\Support\Facades\DB::table('nexus_dead_letter_queue')->insert([
                        'channel' => null, // Could extract from payload if available
                        'job_class' => $event->job->resolveName(),
                        'payload' => json_encode($event->job->payload()),
                        'exception' => (string) $event->exception,
                        'status' => 'failed',
                        'attempts' => $event->job->attempts(),
                        'last_attempt_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    // Fallback log if DB fails
                }
            }
        });

        // Register Webhook Routes
        $this->registerRoutes();
    }

    protected function registerRoutes()
    {
        \Illuminate\Support\Facades\Route::macro('nexusWebhooks', function (string $prefix = 'nexus/webhooks') {
            \Illuminate\Support\Facades\Route::post($prefix.'/{channel}', [\Adnan\LaravelNexus\Http\Controllers\WebhookController::class, 'handle'])
                ->name('nexus.webhooks');
        });

        \Illuminate\Support\Facades\Route::macro('nexusDashboard', function (string $prefix = 'nexus') {
            \Illuminate\Support\Facades\Route::group([
                'prefix' => $prefix,
                'middleware' => config('nexus.dashboard_middleware', ['web']),
            ], function () {
                \Illuminate\Support\Facades\Route::get('/', [\Adnan\LaravelNexus\Http\Controllers\DashboardController::class, 'index'])->name('nexus.dashboard');
                \Illuminate\Support\Facades\Route::get('/jobs', [\Adnan\LaravelNexus\Http\Controllers\DashboardController::class, 'jobs'])->name('nexus.dashboard.jobs');
                \Illuminate\Support\Facades\Route::get('/webhooks', [\Adnan\LaravelNexus\Http\Controllers\DashboardController::class, 'webhooks'])->name('nexus.dashboard.webhooks');
                \Illuminate\Support\Facades\Route::get('/dlq', [\Adnan\LaravelNexus\Http\Controllers\DashboardController::class, 'dlq'])->name('nexus.dashboard.dlq');

                // Actions
                \Illuminate\Support\Facades\Route::post('/dlq/{id}/retry', [\Adnan\LaravelNexus\Http\Controllers\DashboardController::class, 'retryJob'])->name('nexus.dashboard.dlq.retry');
                \Illuminate\Support\Facades\Route::delete('/dlq/{id}', [\Adnan\LaravelNexus\Http\Controllers\DashboardController::class, 'dismissJob'])->name('nexus.dashboard.dlq.dismiss');
            });
        });
    }
}
