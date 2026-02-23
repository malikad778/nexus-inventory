<?php

namespace Malikad778\LaravelNexus\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Malikad778\LaravelNexus\NexusServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    

    public static $latestResponse = null;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Malikad778\\LaravelNexus\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        $providers = [
            NexusServiceProvider::class,
        ];

        if (class_exists('Livewire\LivewireServiceProvider')) {
            $providers[] = 'Livewire\LivewireServiceProvider';
        }

        return $providers;
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('app.key', 'base64:eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHg=');
        config()->set('app.cipher', 'AES-256-CBC');

        $migration = include __DIR__.'/../database/migrations/create_nexus_tables.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_nexus_dlq_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_nexus_webhook_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/patch_nexus_channel_mappings_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_audit_remediation_tables.php.stub';
        $migration->up();
    }
}
